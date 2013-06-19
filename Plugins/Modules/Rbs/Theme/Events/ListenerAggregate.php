<?php
namespace Rbs\Theme\Events;

use Rbs\Theme\Events\ThemeResolver;
use Zend\EventManager\EventManagerInterface;
use Change\Presentation\Themes\ThemeManager;
/**
 * @name \Rbs\Theme\Events\ListenerAggregate
 */
class ListenerAggregate implements \Zend\EventManager\ListenerAggregateInterface
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
		$callback = function (\Zend\EventManager\Event $event)
		{
			$resolver = new ThemeResolver();
			return $resolver->resolve($event);
		};
		$events->attach(ThemeManager::EVENT_LOADING, $callback, 1);
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