<?php
namespace Rbs\Commerce\Http\Rest;

use Rbs\Commerce\Services\CommerceServices;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Http\Event;

/**
 * @name \Rbs\Commerce\Http\ListenerAggregate
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
			$commerceServices = new CommerceServices($event->getApplicationServices(), $event->getDocumentServices());
			$event->setParam('commerceServices', $commerceServices);
		};
		$events->attach(\Change\Http\Event::EVENT_REQUEST, $callback, 5);
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