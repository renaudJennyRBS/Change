<?php
namespace Rbs\Elasticsearch\Events\Admin;

use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Events\Admin\Listeners
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

	public function registerResources(Event $event)
	{
		$manager = $event->getManager();
		$i18nManager = $manager->getApplicationServices()->getI18nManager();

		$pm = $manager->getApplicationServices()->getPluginManager();
		$manager->registerStandardPluginAssets($pm->getModule('Rbs', 'Elasticsearch'));

		$menu = array(
			'entries' => array(
				array('label' => $i18nManager->trans('m.rbs.elasticsearch.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Elasticsearch', 'section' => 'admin',
					'keywords' => $i18nManager->trans('m.rbs.elasticsearch.admin.js.module-keywords'))
			)
		);

		$event->setParam('menu', \Zend\Stdlib\ArrayUtils::merge($event->getParam('menu', array()), $menu));
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
