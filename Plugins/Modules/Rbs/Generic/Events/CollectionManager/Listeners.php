<?php
namespace Rbs\Generic\Events\CollectionManager;

use Change\Collection\CollectionManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\CollectionManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			(new \Rbs\Collection\Events\CollectionResolver())->getCollection($event);
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 5);

		$callback = function (Event $event)
		{
			(new \Rbs\Collection\Events\CollectionResolver())->getCodes($event);
		};
		$events->attach(CollectionManager::EVENT_GET_CODES, $callback, 5);

		$callback = function (Event $event)
		{
			switch ($event->getParam('code'))
			{
				case 'Rbs_Generic_Collection_SortDirections':
					(new \Rbs\Generic\Collection\Collections())->addSortDirections($event);
					break;
				case 'Rbs_Generic_Collection_PermissionRoles':
					(new \Rbs\Generic\Collection\Collections())->addPermissionRoles($event);
					break;
				case 'Rbs_Generic_Collection_PermissionPrivileges':
					(new \Rbs\Generic\Collection\Collections())->addPermissionPrivileges($event);
					break;
				case 'Rbs_Generic_Collection_TimeZones':
					(new \Rbs\Generic\Collection\Collections())->addTimeZones($event);
					break;
				case 'Rbs_Generic_Collection_Languages':
					(new \Rbs\Generic\Collection\Collections())->addLanguages($event);
					break;
				case 'Rbs_Website_AvailablePageFunctions':
					(new \Rbs\Admin\Collection\Collections())->addAvailablePageFunctions($event);
					break;
			}
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 10);

		$callback = function (Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes = array_merge($codes, array(
				'Rbs_Generic_Collection_SortDirections',
				'Rbs_Generic_Collection_PermissionRoles',
				'Rbs_Generic_Collection_PermissionPrivileges',
				'Rbs_Generic_Collection_TimeZones',
				'Rbs_Generic_Collection_Languages',
				'Rbs_Website_AvailablePageFunctions'
			));
			$event->setParam('codes', $codes);
		};
		$events->attach(CollectionManager::EVENT_GET_CODES, $callback, 1);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}