<?php
namespace Change\Website\Events;

use Change\Website\Events\WebsiteResolver;

/**
* @name \Change\Website\Events\SharedListenerAggregate
*/
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		$callback = function (\Change\Http\Event $event)
		{
			$resolver = new WebsiteResolver();
			return $resolver->resolve($event);
		};
		$events->attach('Http.Web', \Change\Http\Event::EVENT_REQUEST, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function detachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		//TODO
	}
}