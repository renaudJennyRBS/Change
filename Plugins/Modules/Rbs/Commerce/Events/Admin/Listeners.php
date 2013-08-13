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
		$i18nManager = $event->getManager()->getApplicationServices()->getI18nManager();

		$body = array(
			'Rbs/Brand/js/admin.js',
			'Rbs/Brand/Brand/controllers.js',
			'Rbs/Brand/Brand/editor.js',
			'Rbs/Catalog/js/admin.js',
			'Rbs/Catalog/Category/controllers.js',
			'Rbs/Catalog/Category/editor.js',
			'Rbs/Catalog/Product/controllers.js',
			'Rbs/Catalog/Product/editor.js',
			'Rbs/Catalog/ProductCategorization/controllers.js',
			'Rbs/Catalog/ProductCategorization/editor.js',
			'Rbs/Catalog/Attribute/controllers.js',
			'Rbs/Catalog/Attribute/editor.js',
			'Rbs/Price/js/admin.js',
			'Rbs/Price/js/directives.js',
			'Rbs/Price/Price/controllers.js',
			'Rbs/Price/Price/editor.js',
			'Rbs/Price/Price/price-list.js',
			'Rbs/Price/Tax/controllers.js',
			'Rbs/Price/Tax/editor.js',
			'Rbs/Price/BillingArea/controllers.js',
			'Rbs/Price/BillingArea/editor.js',
			'Rbs/Store/js/admin.js',
			'Rbs/Store/WebStore/controllers.js',
			'Rbs/Store/WebStore/editor.js',
			'Rbs/Stock/js/admin.js',
			'Rbs/Stock/js/directives.js',
			'Rbs/Stock/Sku/controllers.js',
			'Rbs/Stock/Sku/editor.js',
			'Rbs/Stock/InventoryEntry/controllers.js',
			'Rbs/Stock/InventoryEntry/editor.js'
		);

		$header = array('
    <link href="Rbs/Catalog/css/admin.css" rel="stylesheet"/>
    <link href="Rbs/Price/css/admin.css" rel="stylesheet"/>');

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
					'url' => 'Rbs/Stock', 'section' => 'ecommerce', 'keywords' => $i18nManager->trans('m.rbs.stock.admin.js.module-keywords'))
			)
		);

		$event->setParam('header', array_merge($event->getParam('header'), $header));
		$event->setParam('body', array_merge($event->getParam('body', array()), $body));
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
