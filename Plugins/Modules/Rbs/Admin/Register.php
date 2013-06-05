<?php
namespace Rbs\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Rbs\Admin\Event;

/**
 * @name \Rbs\Admin\Admin\Register
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
			$i18nManager = $event->getManager()->getApplicationServices()->getI18nManager();
			$lcid = strtolower(str_replace('_', '-', $i18nManager->getLCID()));
			$body = array('
	<script type="text/javascript" src="Rbs/Admin/lib/angular/i18n/angular-locale_' . $lcid . '.js">​</script>'
			);
			$event->setParam('body', array_merge($event->getParam('body', array()), $body));

			$menu = array(
				'sections' => array(
					array('code' => 'admin', 'label' => $i18nManager->trans('m.rbs.admin.admin-section-name', array('ucf')))
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
