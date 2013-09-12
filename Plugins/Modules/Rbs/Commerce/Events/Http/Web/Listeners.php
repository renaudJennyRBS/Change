<?php
namespace Rbs\Commerce\Events\Http\Web;


use Change\Http\Web\Event;

use Rbs\Commerce\Http\Web\Loader;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\Http\Web\Listeners
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
		$events->attach(\Change\Http\Event::EVENT_REQUEST,  function (Event $event) {(new Loader)->onRequest($event);}, 10);
		$events->attach(Event::EVENT_AUTHENTICATE, function (Event $event) {(new Loader)->onAuthenticate($event);}, 1);
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