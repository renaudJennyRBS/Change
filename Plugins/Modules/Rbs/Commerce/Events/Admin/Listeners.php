<?php
namespace Rbs\Commerce\Events\Admin;

use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\Admin\Listeners
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
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$pm = $event->getApplicationServices()->getPluginManager();
		foreach ($pm->getInstalledPlugins() as $plugin)
		{
			if ($plugin->getPackage() == "ECom")
			{
				$manager->registerStandardPluginAssets($plugin);
			}
		}

		$menu = array(
			'sections' => array(
				array('code' => 'ecommerce',
					'label' => $i18nManager->trans('m.rbs.catalog.admin.section_name', array('ucf'))),
			),
			'entries' => array(
				array('label' => $i18nManager->trans('m.rbs.catalog.admin.module_name', array('ucf')),
					'url' => 'Rbs/Catalog', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.catalog.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.brand.admin.module_name', array('ucf')),
					'url' => 'Rbs/Brand', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.brand.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.price.admin.module_name', array('ucf')),
					'url' => 'Rbs/Price', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.price.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.store.admin.module_name', array('ucf')),
					'url' => 'Rbs/Store', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.store.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.stock.admin.module_name', array('ucf')),
					'url' => 'Rbs/Stock', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.stock.admin.module_keywords')),

				array('label' => $i18nManager->trans('m.rbs.order.admin.module_name', array('ucf')),
					'url' => 'Rbs/Order', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.order.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.payment.admin.module_name', array('ucf')),
					'url' => 'Rbs/Payment', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.payment.admin.module_keywords')),
				array('label' => $i18nManager->trans('m.rbs.shipping.admin.module_name', array('ucf')),
					'url' => 'Rbs/Shipping', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.shipping.admin.module_keywords'))
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
