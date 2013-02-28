<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\Link;
/**
 * @name \Change\Http\Rest\Actions\GetDocumentModelCollection
 */
class GetDocumentModelCollection
{
	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$modelName = $event->getParam('modelName');
		$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
		if (!$model)
		{
			throw new \RuntimeException('Invalid Parameter: modelName', 71000);
		}
		$this->generateResult($event, $model);
	}

	/**
	 * @param \Change\Http\Rest\Result\CollectionResult $result
	 * @return array
	 */
	protected function buildQueryArray($result)
	{
		$array = array('limit' => $result->getLimit(), 'offset' => $result->getOffset());
		if ($result->getSort())
		{
			$array['sort'] = $result->getSort();
			$array['desc'] = ($result->getDesc()) ? 'true' : 'false';
		}
		return $array;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Documents\AbstractModel $model
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event, $model)
	{
		$urlManager = $event->getUrlManager();
		$result = new \Change\Http\Rest\Result\CollectionResult();
		if (($offset = $event->getRequest()->getQuery('offset')) !== null)
		{
			$result->setOffset(intval($offset));
		}
		if (($limit = $event->getRequest()->getQuery('limit')) !== null)
		{
			$result->setLimit(intval($limit));
		}
		if (($sort = $event->getRequest()->getQuery('sort')) !== null)
		{
			$result->setSort($sort);
		}

		if (($desc = $event->getRequest()->getQuery('desc')) !== null)
		{
			$result->setDesc($desc);
		}

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$table = $fb->getDocumentTable($model->getRootName());

		$sc = $qb->select()
				->addColumn($fb->alias($fb->func('count', $fb->getDocumentColumn('id')), 'count'))
				->from($table)->query();
		$row = $sc->getFirstResult();
		if ($row && $row['count'])
		{
			$result->setCount(intval($row['count']));
		}

		if ($result->getOffset())
		{
			$prevLink = clone($selfLink);
			$prevOffset = max(0, $result->getOffset() - $result->getLimit());
			$query = $this->buildQueryArray($result);
			$query['offset'] = $prevOffset;
			$prevLink->setQuery($query);
			$prevLink->setRel('prev');
			$result->addLink($prevLink);
		}

		if ($result->getCount())
		{
			if ($result->getCount() > $result->getOffset() + $result->getLimit())
			{
				$nextLink = clone($selfLink);
				$nextOffset = min($result->getOffset() + $result->getLimit(), $result->getCount() - 1);
				$query = $this->buildQueryArray($result);
				$query['offset'] = $nextOffset;
				$nextLink->setQuery($query);
				$nextLink->setRel('next');
				$result->addLink($nextLink);
			}

			$qb->select()
				->addColumn($fb->alias($fb->getDocumentColumn('id', $table), 'id'))
				->addColumn($fb->alias($fb->getDocumentColumn('model', $table), 'model'))
				->from($table);

			if ($result->getSort() && ($property = $model->getProperty($result->getSort())) !== null)
			{
				if ($property->getLocalized())
				{
					$LCID = $event->getLCID();
					$i18nTable = $fb->getDocumentI18nTable($model->getRootName());

					$qb->leftJoin($i18nTable,
						$fb->logicAnd(
							$fb->eq($fb->getDocumentColumn('id', $table), $fb->getDocumentColumn('id', $i18nTable)),
							$fb->eq($fb->getDocumentColumn('LCID', $i18nTable), $fb->string($LCID))
						)
					);
				}

				if ($property->getName() == 'id' || $property->getName() == 'model')
				{
					$orderColumn = $fb->column($property->getName());
				}
				else
				{
					$orderColumn = $fb->getDocumentColumn($property->getName());
				}

				if ($result->getDesc())
				{
					$qb->orderDesc($orderColumn);
				}
				else
				{
					$qb->orderAsc($orderColumn);
				}
			}

			$sc = $qb->query();
			$sc->setMaxResults($result->getLimit());
			$sc->setStartIndex($result->getOffset());
			$collection = new \Change\Documents\DocumentCollection($event->getDocumentServices()->getDocumentManager(), $sc->getResults());
			foreach ($collection as $document)
			{
				$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
				$result->addResource($this->addResourceItemInfos($l, $document, $urlManager));
			}
		}

		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param DocumentLink $documentLink
	 * @param \Change\Documents\AbstractDocument $document
	 * @param \Change\Http\UrlManager $urlManager
	 * @return DocumentLink
	 */
	protected function addResourceItemInfos(DocumentLink $documentLink, \Change\Documents\AbstractDocument $document, \Change\Http\UrlManager $urlManager)
	{
		if ($documentLink->getLCID())
		{
			$document->getDocumentManager()->pushLCID($documentLink->getLCID());
		}

		$model = $document->getDocumentModel();

		$documentLink->setProperty($model->getProperty('creationDate'));
		$documentLink->setProperty($model->getProperty('modificationDate'));

		if ($document instanceof \Change\Documents\Interfaces\Editable)
		{
			$documentLink->setProperty($model->getProperty('label'));
			$documentLink->setProperty($model->getProperty('documentVersion'));
		}

		if ($document instanceof \Change\Documents\Interfaces\Publishable)
		{
			$documentLink->setProperty($model->getProperty('publicationStatus'));
		}

		if ($document instanceof \Change\Documents\Interfaces\Localizable)
		{
			$documentLink->setProperty($model->getProperty('refLCID'));
			$documentLink->setProperty($model->getProperty('LCID'));
		}

		if ($model->useCorrection())
		{
			$cf = $document->getCorrectionFunctions();
			if ($cf->hasCorrection())
			{
				$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
				$documentLink->setProperty('actions', array($l));
			}
		}

		if ($documentLink->getLCID())
		{
			$document->getDocumentManager()->popLCID();
		}
		return $documentLink;
	}
}
