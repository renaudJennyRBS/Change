<?php
namespace Rbs\Commerce\Events\JobManager;

use Change\Job\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\JobManager\Listeners
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
		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\InitializeItemsForSectionList())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_InitializeItemsForSectionList', $callBack, 5);
		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\CleanUpListItems())->execute($event);
		};
		$events->attach('process_Change_Document_CleanUp', $callBack, 10);
		$callBack = function ($event)
		{
			(new \Rbs\Catalog\Job\UpdateSymmetricalProductListItem())->execute($event);
		};
		$events->attach('process_Rbs_Catalog_UpdateSymmetricalProductListItem', $callBack, 15);
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