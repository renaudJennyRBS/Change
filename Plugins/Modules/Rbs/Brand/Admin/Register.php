<?php
namespace Rbs\Brand\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Rbs\Admin\Event;
/**
 * @name \Rbs\Brand\Admin\Register
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
			$body = array('
	<script type="text/javascript" src="Rbs/Brand/js/admin.js">​</script>
	<script type="text/javascript" src="Rbs/Brand/Brand/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Brand/Brand/editor.js">​</script>');
			$event->setParam('body', array_merge($event->getParam('body'), $body));

			$i18nManager = $event->getManager()->getApplicationServices()->getI18nManager();
			$menu = array(
				'entries' => array(
					array('label' => $i18nManager->trans('m.rbs.brand.admin.js.module-name', array('ucf'))
					, 'url' => 'Rbs/Brand', 'section' => 'ecommerce', 'keywords' => $i18nManager->trans('m.rbs.brand.admin.js.module-keywords'))
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
