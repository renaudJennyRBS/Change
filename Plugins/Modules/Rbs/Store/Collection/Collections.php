<?php
namespace Rbs\Store\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Store\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addWebStores(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$collection = array();
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Store_WebStore');
			$builder = $query->dbQueryBuilder();
			$fb = $builder->getFragmentBuilder();
			$builder->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'));
			$builder->addColumn($fb->alias($fb->getDocumentColumn('label'), 'label'));
			$selectQuery = $builder->query();
			$rows = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id')->addStrCol('label'));
			foreach ($rows as $row)
			{
				$collection[$row['id']] = $row['label'];
			}
			$collection = new CollectionArray('Rbs_Store_Collection_WebStores', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}