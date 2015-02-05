<?php
namespace Rbs\Social\Events\AdminManager;

use Change\Http\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Social\Events\Http\Rest\Listeners
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
		$callback = function ($event)
		{
			(new \Rbs\Social\Admin\GetModelTwigAttributes())->execute($event);
		};
		//Priority 1 (default value) to be sure to get the default attributes
		$events->attach('getModelTwigAttributes', $callback);

		$callback = function ($event)
		{
			(new \Rbs\Social\Admin\GetRoutes())->execute($event);
		};
		$events->attach('getRoutes', $callback);
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