<?php
namespace Rbs\Users\Events;

use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
* @name \Rbs\Users\Events\SharedListenerAggregate
*/
class SharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$loginListener = new Login();
			$loginListener->execute($event);
		};

		$events->attach(\Change\User\AuthenticationManager::EVENT_MANAGER_IDENTIFIER,
			\Change\User\AuthenticationManager::EVENT_LOGIN, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		//TODO
	}
}