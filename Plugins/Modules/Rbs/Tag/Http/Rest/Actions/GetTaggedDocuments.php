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
 * @name \Rbs\Tag\Http\Rest\Actions\GetTaggedDocuments
 */
class GetTaggedDocuments
{
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

		$requestedModelName = $event->getParam('requestedModelName');

		$tagId = $event->getParam('tagId');

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select(
			$fb->alias($fb->getDocumentColumn('id'), 'id'),
			$fb->alias($fb->func('count', $fb->getDocumentColumn('id')), 'count')
		);
		$qb->from($fb->getDocumentIndexTable());
		$qb->innerJoin($fb->alias($fb->table('rbs_tag_rel_document'), 'tag'), $fb->eq($fb->column('doc_id', 'tag'), 'id'));

		if ($requestedModelName)
		{
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('tag_id', 'tag'), $fb->integerParameter('tagId')),
				$fb->eq($fb->getDocumentColumn('model'), $fb->parameter('model'))
			));
		}
		else
		{
			$qb->where($fb->eq($fb->column('tag_id', 'tag'), $fb->integerParameter('tagId')));
		}

		$sc = $qb->query();
		$sc->bindParameter('tagId', $tagId);
		if ($requestedModelName)
		{
			$sc->bindParameter('model', $requestedModelName);
		}

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

			$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select(
				$fb->alias($fb->getDocumentColumn('id'), 'id'),
				$fb->alias($fb->getDocumentColumn('model'), 'model')
			);
			$qb->from($fb->getDocumentIndexTable());
			$qb->innerJoin($fb->alias($fb->table('rbs_tag_rel_document'), 'tag'), $fb->eq($fb->column('doc_id', 'tag'), 'id'));
			if ($requestedModelName)
			{
				$qb->where($fb->logicAnd(
					$fb->eq($fb->column('tag_id', 'tag'), $fb->integerParameter('tagId')),
					$fb->eq($fb->getDocumentColumn('model'), $fb->parameter('model'))
				));
			}
			else
			{
				$qb->where($fb->eq($fb->column('tag_id', 'tag'), $fb->integerParameter('tagId')));
			}
			$sc = $qb->query();
			$sc->bindParameter('tagId', $tagId);
			if ($requestedModelName)
			{
				$sc->bindParameter('model', $requestedModelName);
			}
			$sc->setMaxResults($result->getLimit());
			$sc->setStartIndex($result->getOffset());
			$collection = new DocumentCollection($event->getDocumentServices()->getDocumentManager(), $sc->getResults());
			foreach ($collection as $document)
			{
				$l = new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY);
				$result->addResource($this->addResourceItemInfos($l, $document, $urlManager, null));
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
