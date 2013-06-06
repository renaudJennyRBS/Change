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
		$code = $event->getParam('code');
		$query = new Query($event->getParam('documentServices'),'Rbs_Collection_Collection');
		$collection = $query->andPredicates($query->eq('code', $code))->getFirstDocument();
		$event->setParam('collection', $collection);
	}

	/**
	 * @param Event $event
	 */
	public function getCodes(Event $event)
	{
		$codes = $event->getParam('codes');
		$documentServices = $event->getParam('documentServices');
		if (!is_array($codes))
		{
			$codes = array();
		}

		if ($documentServices instanceof DocumentServices)
		{
			$query = new Query($documentServices, 'Rbs_Collection_Collection');
			$qb = $query->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->alias($query->getColumn('code'), 'code'));
			foreach($qb->query()->getResults() as $row)
			{
				$codes[] = $row['code'];
			};
			$codes = array_unique($codes);
			$event->setParam('codes', $codes);
		}
	}
}