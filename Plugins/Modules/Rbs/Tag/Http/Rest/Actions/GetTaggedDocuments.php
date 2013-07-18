<?php
namespace Rbs\Tag\Http\Rest\Actions;

use Change\Documents\DocumentCollection;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

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
		$qb->innerJoin($fb->alias($fb->table('rbs_tag_document'), 'tag'), $fb->eq($fb->column('doc_id', 'tag'), 'id'));

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
			$qb->innerJoin($fb->alias($fb->table('rbs_tag_document'), 'tag'), $fb->eq($fb->column('doc_id', 'tag'), 'id'));
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
				$result->addResource($l->addResourceItemInfos($document, $urlManager, null));
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}
