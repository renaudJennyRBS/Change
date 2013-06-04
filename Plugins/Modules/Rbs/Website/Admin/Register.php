<?php
namespace Rbs\Website\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Rbs\Admin\Event;
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
		$events->attach(Event::EVENT_RESOURCES, function(Event $event)
		{
			$header =  array('<link href="Rbs/Website/css/admin.css" rel="stylesheet"/>');
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
