<?php
namespace Rbs\Store\Collection;

use Change\Collection\CollectionArray;
use Change\Documents\Query\Query;

/**
 * @name \Rbs\Store\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function addWebStores(\Zend\EventManager\Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof \Change\Documents\DocumentServices)
		{
			$collection = array();
			$query = new Query($documentServices, 'Rbs_Store_WebStore');
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