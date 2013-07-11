<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentCollection;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\GetDocumentModelCollection
 */
class GetDocumentModelCollection
{
	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
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
	 * @param CollectionResult $result
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
		$result = new CollectionResult();
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

		if ($model->isStateless())
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setCount(0);
			$event->setResult($result);
			return $result;
		}

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$table = $fb->getDocumentTable($model->getRootName());

		$qb->select()->addColumn($fb->alias($fb->func('count', $fb->getDocumentColumn('id')), 'count'))
			->from($table);

		if ($model->hasDescendants())
		{
			$qb->where($fb->in($fb->getDocumentColumn('model'), $model->getName(), $model->getDescendantsNames()));
		}
		else
		{
			$qb->where($fb->eq($fb->getDocumentColumn('model'), $fb->string($model->getName())));
		}

		$sc = $qb->query();
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

			if ($model->hasDescendants())
			{
				$qb->where($fb->in($fb->getDocumentColumn('model'), $model->getName(), $model->getDescendantsNames()));
			}
			else
			{
				$qb->where($fb->eq($fb->getDocumentColumn('model'), $fb->string($model->getName())));
			}

			if ($result->getSort() && ($property = $model->getProperty($result->getSort())) !== null)
			{
				if ($property->getLocalized())
				{
					$LCID = $event->getRequest()->getLCID();
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
			$extraColumn = $event->getRequest()->getQuery('column', array());
			$sc = $qb->query();
			$sc->setMaxResults($result->getLimit());
			$sc->setStartIndex($result->getOffset());
			$collection = new DocumentCollection($event->getDocumentServices()->getDocumentManager(), $sc->getResults());
			foreach ($collection as $document)
			{
				$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
				$result->addResource($this->addResourceItemInfos($l, $document, $urlManager, $extraColumn));
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}

	/**
	 * @param DocumentLink $documentLink
	 * @param AbstractDocument $document
	 * @param UrlManager $urlManager
	 * @param array $extraColumn
	 * @return DocumentLink
	 */
	protected function addResourceItemInfos(DocumentLink $documentLink, AbstractDocument $document, UrlManager $urlManager,
		$extraColumn)
	{
		$dm = $document->getDocumentServices()->getDocumentManager();
		$eventManager = $document->getEventManager();

		if ($documentLink->getLCID())
		{
			$dm->pushLCID($documentLink->getLCID());
		}

		$model = $document->getDocumentModel();

		$documentLink->setProperty($model->getProperty('creationDate'));
		$documentLink->setProperty($model->getProperty('modificationDate'));

		if ($document instanceof Editable)
		{
			$documentLink->setProperty($model->getProperty('label'));
			$documentLink->setProperty($model->getProperty('documentVersion'));
		}

		if ($document instanceof Publishable)
		{
			$documentLink->setProperty($model->getProperty('publicationStatus'));
		}

		if ($document instanceof Localizable)
		{
			$documentLink->setProperty($model->getProperty('refLCID'));
			$documentLink->setProperty($model->getProperty('LCID'));
		}

		if ($document instanceof Correction)
		{
			/* @var $document AbstractDocument|Correction */
			if ($document->hasCorrection())
			{
				$l = new DocumentActionLink($urlManager, $document, 'getCorrection');
				$documentLink->setProperty('actions', array($l));
			}
		}

		if (is_array($extraColumn) && count($extraColumn))
		{
			foreach ($extraColumn as $propertyName)
			{
				$property = $model->getProperty($propertyName);
				if ($property)
				{
					$documentLink->setProperty($property);
				}
			}
		}

		$documentEvent = new \Change\Documents\Events\Event('updateRestResult', $document,
			array('restResult' => $documentLink, 'extraColumn' => $extraColumn, 'urlManager' => $urlManager));
		$eventManager->trigger($documentEvent);

		if ($documentLink->getLCID())
		{
			$dm->popLCID();
		}

		return $documentLink;
	}
}
