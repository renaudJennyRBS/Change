<?php
namespace Rbs\Tag\Http\Rest\Actions;

use Change\Documents\DocumentCollection;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

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
		$qb->innerJoin($fb->alias($fb->table('rbs_tag_document'), 'tag'),
			$fb->eq($fb->column('tag_id', 'tag'), $fb->getDocumentColumn('id')));
		$qb->where($fb->eq($fb->column('doc_id', 'tag'), $fb->integerParameter('docId')));
		$sc = $qb->query();
		$sc->bindParameter('docId', $docId);
		$sc->setMaxResults(self::MAX_TAGS);
		$collection = new DocumentCollection($event->getDocumentServices()->getDocumentManager(), $sc->getResults());
		$result->setCount($collection->count());
		$result->setLimit(self::MAX_TAGS);

		foreach ($collection as $document)
		{
			$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, array('color', 'userTag')));
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$event->setResult($result);
		return $result;
	}
}
