<?php
namespace Change\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Documents\Query\JSONDecoder;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\DocumentQuery
 */
class DocumentQuery
{
	/**
	 * @var \Change\Documents\TreeManager
	 */
	protected $treeManager;

	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$query = $event->getRequest()->getPost()->toArray();
		if (is_array($query) && isset($query['model']))
		{
			$modelName = $query['model'];

			$model = $event->getDocumentServices()->getModelManager()->getModelByName($modelName);
			if (!$model)
			{
				throw new \RuntimeException('Invalid Parameter: model', 71000);
			}
			$startIndex = isset($query['offset']) ? intval($query['offset']) : 0;
			$maxResults = isset($query['limit']) ? intval($query['limit']) : 10;
			$LCID = isset($query['LCID']) ? $query['LCID'] : null;

			$urlManager = $event->getUrlManager();
			$result = new CollectionResult();
			$selfLink = new Link($urlManager, $event->getRequest()->getPath());
			$result->addLink($selfLink);
			$result->setOffset($startIndex);
			$result->setLimit($maxResults);
			$result->setSort(null);

			$this->treeManager = $event->getDocumentServices()->getTreeManager();

			if ($LCID)
			{
				$event->getDocumentServices()->getDocumentManager()->pushLCID($LCID);
			}

			try
			{
				$decoder = new JSONDecoder();
				$decoder->setDocumentServices($event->getDocumentServices());

				$queryBuilder = $decoder->getQuery($query);
				$count = $queryBuilder->getCountDocuments();
				$result->setCount($count);
				if ($count && $startIndex < $count)
				{
					$extraColumn = $event->getRequest()->getQuery('column', array());
					$collection = $queryBuilder->getDocuments($startIndex, $maxResults);
					foreach ($collection as $document)
					{
						$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
						$result->addResource($this->addResourceItemInfos($l, $document, $urlManager, $extraColumn));
					}
				}

				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$event->setResult($result);

				if ($LCID)
				{
					$event->getDocumentServices()->getDocumentManager()->popLCID();
				}
			}
			catch (\Exception $e)
			{
				if ($LCID)
				{
					$event->getDocumentServices()->getDocumentManager()->popLCID();
				}
				throw $e;
			}
		}
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
		$dm = $document->getDocumentManager();
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

		if ($documentLink->getLCID())
		{
			$dm->popLCID();
		}
		return $documentLink;
	}
}