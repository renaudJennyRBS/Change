<?php
namespace Rbs\Website\Admin;

use Rbs\Admin\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Website\Admin\Register
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
		$events->attach(Event::EVENT_RESOURCES, function (Event $event)
		{
			$header = array('<link href="Rbs/Website/css/admin.css" rel="stylesheet"/>');
			$event->setParam('header', array_merge($event->getParam('header'), $header));

			$body = array('
	<script type="text/javascript" src="Rbs/Website/js/admin.js">​</script>
	<script type="text/javascript" src="Rbs/Website/StaticPage/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Website/StaticPage/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Topic/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Topic/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Website/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Website/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Menu/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Menu/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Menu/directives/menu-card.js">​</script>
	<script type="text/javascript" src="Rbs/Website/Menu/directives/menu-functions.js">​</script>');
			$event->setParam('body', array_merge($event->getParam('body'), $body));

			$i18nManager = $event->getManager()->getApplicationServices()->getI18nManager();

			$menu = array(
				'sections' => array(
					array('code' => 'cms', 'label' => $i18nManager->trans('m.rbs.website.admin.section-name', array('ucf')))
				),
				'entries' => array(
					array('label' => $i18nManager->trans('m.rbs.website.admin.js.module-name', array('ucf')),
						'url' => 'Rbs/Website', 'section' => 'cms', 'keywords' => $i18nManager->trans('m.rbs.website.admin.js.module-keywords'))
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
