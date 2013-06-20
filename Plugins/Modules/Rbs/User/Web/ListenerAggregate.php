<?php
namespace Rbs\User\Web;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Http\Event;

/**
 * @name \Rbs\User\Web\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface
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
			$loginListener = new Login();
			$loginListener->authenticate($event);
		};
		$events->attach(\Change\Http\Event::EVENT_AUTHENTICATE, $callback, 5);
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