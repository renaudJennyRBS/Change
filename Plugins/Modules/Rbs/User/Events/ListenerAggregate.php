<?php
namespace Rbs\User\Events;

use Change\User\AuthenticationManager;
use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\User\Events\ListenerAggregate
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
			$loginListener->execute($event);
		};
		$events->attach(array(AuthenticationManager::EVENT_LOGIN, AuthenticationManager::EVENT_BY_USER_ID), $callback, 5);
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