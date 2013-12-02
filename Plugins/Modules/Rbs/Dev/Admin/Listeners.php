<?php
namespace Rbs\Dev\Admin;

use Change\Plugins\Plugin;
use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Dev\Admin\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(Event::EVENT_RESOURCES, array($this, 'registerResources'));
	}

	/**
	 * @param Event $event
	 */
	public function registerResources(Event $event)
	{
		$manager = $event->getManager();
		$pm = $event->getApplicationServices()->getPluginManager();

		$plugin = $pm->getPlugin(Plugin::TYPE_MODULE, 'Rbs', 'Dev');
		$manager->registerStandardPluginAssets($plugin);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}
