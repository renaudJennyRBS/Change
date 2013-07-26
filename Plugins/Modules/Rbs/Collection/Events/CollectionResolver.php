<?php
namespace Rbs\Collection\Events;

use Change\Documents\DocumentServices;
use Change\Documents\Query\Query;
use Zend\EventManager\Event;

/**
 * @name \Rbs\Collection\Events\CollectionResolver
 */
class CollectionResolver
{
	/**
	 * @param Event $event
	 */
	public function getCollection(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof DocumentServices)
		{
			$code = $event->getParam('code');
			$query = new Query($documentServices, 'Rbs_Collection_Collection');
			$collection = $query->andPredicates($query->eq('code', $code))->getFirstDocument();
			if ($collection)
			{
				$event->setParam('collection', $collection);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function getCodes(Event $event)
	{
		$documentServices = $event->getParam('documentServices');
		if ($documentServices instanceof DocumentServices)
		{
			$codes = $event->getParam('codes');
			if (!is_array($codes))
			{
				$codes = array();
			}
			$query = new Query($documentServices, 'Rbs_Collection_Collection');
			$qb = $query->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->alias($query->getColumn('code'), 'code'));
			foreach ($qb->query()->getResults() as $row)
			{
				$codes[] = $row['code'];
			};
			$codes = array_unique($codes);
			$event->setParam('codes', $codes);
		}
	}
}