<?php
namespace Rbs\Commerce\Events\Admin;

use Assetic\Asset\GlobAsset;
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
		$i18nManager = $manager->getApplicationServices()->getI18nManager();
		$pm = $manager->getApplicationServices()->getPluginManager();
		foreach ($pm->getInstalledPlugins() as $plugin)
		{
			if ($plugin->getPackage() == "ECom")
			{
				$jsAssets = new GlobAsset($plugin->getBasePath() . '/Admin/Assets/*/*.js');
				$jsAssets->ensureFilter(new \Assetic\Filter\JSMinFilter());
				$manager->getJsAssetManager()->set($plugin->getName(), $jsAssets);

				$cssAsset = new GlobAsset($plugin->getBasePath() . '/Admin/Assets/css/*.css');
				$manager->getCssAssetManager()->set($plugin->getName(), $cssAsset);
			}
		}

		$menu = array(
			'sections' => array(
				array('code' => 'ecommerce',
					'label' => $i18nManager->trans('m.rbs.catalog.admin.js.section-name', array('ucf'))),
			),
			'entries' => array(
				array('label' => $i18nManager->trans('m.rbs.catalog.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Catalog', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.catalog.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.brand.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Brand', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.brand.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.price.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Price', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.price.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.store.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Store', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.store.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.stock.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Stock', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.stock.admin.js.module-keywords')),

				array('label' => $i18nManager->trans('m.rbs.order.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Order', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.order.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.payment.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Payment', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.payment.admin.js.module-keywords')),
				array('label' => $i18nManager->trans('m.rbs.shipping.admin.js.module-name', array('ucf')),
					'url' => 'Rbs/Shipping', 'section' => 'ecommerce',
					'keywords' => $i18nManager->trans('m.rbs.shipping.admin.js.module-keywords'))
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
