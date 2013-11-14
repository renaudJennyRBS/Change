<?php
namespace Rbs\Event\Events\Admin;

use Change\Plugins\Plugin;
use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Event\Events\Admin\Listeners
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

		$plugin = $pm->getPlugin(Plugin::TYPE_MODULE, 'Rbs', 'Event');
		if ($plugin && $plugin->isAvailable())
		{
			$manager->registerStandardPluginAssets($plugin);

			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$menu = array(
				'entries' => array(
					array('label' => $i18nManager->trans('m.rbs.event.admin.module_name', array('ucf')),
						'url' => 'Rbs/Event', 'section' => 'cms',
						'keywords' => $i18nManager->trans('m.rbs.event.admin.module_keywords'))
				)
			);
			$event->setParam('menu', \Zend\Stdlib\ArrayUtils::merge($event->getParam('menu', array()), $menu));
		}
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
