<?php
namespace Rbs\Tag\Http\Rest\Actions;

use Change\Documents\AbstractDocument;
use Change\Documents\DocumentCollection;
use Change\Documents\Interfaces\Correction;
use Change\Documents\Interfaces\Editable;
use Change\Documents\Interfaces\Localizable;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Rest\Result\CollectionResult;
use \Change\Documents\Query\Builder;
use Change\Http\UrlManager;
use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentActionLink;
use Change\Http\Rest\Result\Link;
/**
 * @name \Rbs\Tag\Http\Rest\Actions\GetDocumentTags
 */
class GetDocumentTags
{

	const MAX_TAGS = 1000;

	/**
	 * Use Event Params: documentId, modelName, LCID
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$this->generateResult($event);
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
	 * @return \Change\Http\Rest\Result\DocumentResult
	 */
	protected function generateResult($event)
	{
		$urlManager = $event->getUrlManager();
		$result = new CollectionResult();

		$selfLink = new Link($urlManager, $event->getRequest()->getPath());
		$selfLink->setQuery($this->buildQueryArray($result));
		$result->addLink($selfLink);

		$docId = $event->getParam('docId');

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select(
			$fb->alias($fb->getDocumentColumn('id'), 'id'),
			$fb->alias($fb->getDocumentColumn('model'), 'model')
		);
		$qb->from($fb->getDocumentTable('Rbs_Tag_Tag'));
		$qb->innerJoin($fb->alias($fb->table('rbs_tag_rel_document'), 'tag'), $fb->eq($fb->column('tag_id', 'tag'), 'id'));
		$qb->where($fb->eq($fb->column('doc_id', 'tag'), $fb->integerParameter('docId')));
		$sc = $qb->query();
		$sc->bindParameter('docId', $docId);
		$sc->setMaxResults(self::MAX_TAGS);
		$collection = new DocumentCollection($event->getDocumentServices()->getDocumentManager(), $sc->getResults());
		$result->setCount($collection->count());
		$result->setLimit(self::MAX_TAGS);

		foreach ($collection as $document)
		{
			$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
			$result->addResource($this->addResourceItemInfos($l, $document, $urlManager, array('color','userTag')));
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
	protected function addResourceItemInfos(DocumentLink $documentLink, AbstractDocument $document, UrlManager $urlManager, $extraColumn)
	{
		$dm = $document->getDocumentServices()->getDocumentManager();
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
