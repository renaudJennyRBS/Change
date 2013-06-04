<?php
namespace Rbs\Theme\Events;

use Rbs\Theme\Events\ThemeResolver;

/**
* @name \Rbs\Theme\Events\SharedListenerAggregate
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
		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new ThemeResolver();
			return $resolver->resolve($event);
		};
		$events->attach('Presentation.Themes', 'loading', $callback, 1);
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