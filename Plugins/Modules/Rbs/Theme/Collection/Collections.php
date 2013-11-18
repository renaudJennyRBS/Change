<?php
namespace Rbs\Theme\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Theme\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addWebsiteIds(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Website_Website');
			$builder = $query->dbQueryBuilder();
			$fb = $builder->getFragmentBuilder();
			$builder->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'));
			$builder->addColumn($fb->alias($fb->getDocumentColumn('label'), 'label'));
			$selectQuery = $builder->query();
			$rows = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id')->addStrCol('label')->indexBy('id'));
			$collection = new CollectionArray('Rbs_Theme_WebsiteIds', $rows);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}