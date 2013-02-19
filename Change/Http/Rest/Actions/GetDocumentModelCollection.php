<?php
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
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
			return;
		}
		$this->generateResult($event, $model);
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
		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery(array('limit' => $result->getLimit(), 'offset' => $result->getOffset()));
		$result->addLink($selfLink);

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$sc = $qb->select()
				->addColumn($fb->alias($fb->func('count', $fb->getDocumentColumn('id')), 'count'))
				->from($fb->getDocumentTable($model->getRootName()))->query();
		$row = $sc->getFirstResult();
		if ($row && $row['count'])
		{
			$result->setCount(intval($row['count']));
		}

		if ($result->getOffset())
		{
			$prevLink = clone($selfLink);
			$prevOffset = max(0, $result->getOffset() - $result->getLimit());
			$prevLink->setQuery(array('limit' => $result->getLimit(), 'offset' => $prevOffset));
			$prevLink->setRel('prev');
			$result->addLink($prevLink);
		}

		if ($result->getCount())
		{
			if ($result->getCount() > $result->getOffset() + $result->getLimit())
			{
				$nextLink = clone($selfLink);
				$nextOffset = min($result->getOffset() + $result->getLimit(), $result->getCount() - 1);
				$nextLink->setQuery(array('limit' => $result->getLimit(), 'offset' => $nextOffset));
				$nextLink->setRel('next');
				$result->addLink($nextLink);
			}

			$sc = $qb->select()
				->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'))
				->addColumn($fb->alias($fb->getDocumentColumn('model'), 'model'))
				->from($fb->getDocumentTable($model->getRootName()))->query();
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

		if ($documentLink->getLCID())
		{
			$document->getDocumentManager()->popLCID();
		}
		return $documentLink;
	}
}
