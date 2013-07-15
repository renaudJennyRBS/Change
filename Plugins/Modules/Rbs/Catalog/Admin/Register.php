<?php
namespace Rbs\Catalog\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Rbs\Admin\Event;
/**
 * @name \Rbs\Catalog\Admin\Register
 */
class Register implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(Event::EVENT_RESOURCES, function(Event $event)
		{
			$header =  array('<link href="Rbs/Catalog/css/admin.css" rel="stylesheet"/>');
			$event->setParam('header', array_merge($event->getParam('header'), $header));

			$body = array('
	<script type="text/javascript" src="Rbs/Catalog/js/admin.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Category/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Category/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Category/picker.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Product/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Product/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Shop/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Shop/editor.js">​</script>');
			$event->setParam('body', array_merge($event->getParam('body'), $body));

			$i18nManager = $event->getManager()->getApplicationServices()->getI18nManager();
			$menu = array(
				'sections' => array(
					array('code' => 'ecommerce',
						'label' => $i18nManager->trans('m.rbs.catalog.admin.js.section-name', array('ucf'))),
				),
				'entries' => array(
					array('label' => $i18nManager->trans('m.rbs.catalog.admin.js.module-name', array('ucf'))
					, 'url' => 'Rbs/Catalog', 'section' => 'ecommerce', 'keywords' => $i18nManager->trans('m.rbs.catalog.admin.js.module-keywords'))
				));

			$event->setParam('menu', \Zend\Stdlib\ArrayUtils::merge($event->getParam('menu', array()), $menu));
		});
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
