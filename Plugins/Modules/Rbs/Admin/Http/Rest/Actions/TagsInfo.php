<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Change\Http\Rest\PropertyConverter;
use Change\Http\Rest\Result\ArrayResult;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\TagsInfo
 */
class TagsInfo
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$result = new ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$urlManager = $event->getUrlManager();

		$tagIds = $event->getRequest()->getQuery('tags');
		if (!is_array($tagIds))
		{
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$model = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Tag_Tag');
		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$tags = [];
		foreach ($tagIds as $tagId)
		{
			/* @var $tag \Rbs\Tag\Documents\Tag */
			$tag = $documentManager->getDocumentInstance($tagId, $model);

			$properties = array();
			foreach ($model->getProperties() as $name => $property)
			{
				$c = new PropertyConverter($tag, $property, $documentManager, $urlManager);
				$properties[$name] = $c->getRestValue();
			}
			$properties['documentCount'] = $this->getDocumentCountForTag($qb, $tagId);
			$tags[] = $properties;
		}

		$result->setArray($tags);
		$event->setResult($result);
	}

	/**
	 * @param \Change\Db\Query\Builder $qb
	 * @param int $tagId
	 * @return int
	 */
	protected function getDocumentCountForTag($qb, $tagId)
	{
		$fb = $qb->getFragmentBuilder();
		$qb->select(
			$fb->alias($fb->getDocumentColumn('id'), 'id'),
			$fb->alias($fb->func('count', $fb->getDocumentColumn('id')), 'count')
		);
		$qb->from($fb->getDocumentIndexTable());
		$qb->innerJoin($fb->alias($fb->table('rbs_tag_document'), 'tag'), $fb->eq($fb->column('doc_id', 'tag'), 'id'));

		$qb->where($fb->eq($fb->column('tag_id', 'tag'), $fb->integerParameter('tagId')));

		$sc = $qb->query();
		$sc->bindParameter('tagId', $tagId);

		$row = $sc->getFirstResult();
		return $row && $row['count'] ? intval($row['count']) : 0;
	}
}