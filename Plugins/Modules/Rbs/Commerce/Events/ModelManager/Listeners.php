<?php
namespace Rbs\Commerce\Events\ModelManager;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\ModelManager\Listeners
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
		$callback = function (\Change\Events\Event $event)
		{
			(new \Rbs\Catalog\Events\ModelManager())->getFiltersDefinition($event);
		};
		$events->attach('getFiltersDefinition', $callback, 1);

		$callback = function (\Change\Events\Event $event)
		{
			(new \Rbs\Catalog\Events\ModelManager())->getRestriction($event);
		};
		$events->attach('getRestriction', $callback, 1);
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