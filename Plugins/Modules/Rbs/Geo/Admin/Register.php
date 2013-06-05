<?php
namespace Rbs\Geo\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Rbs\Admin\Event;

/**
 * @name \Rbs\Geo\Admin\Register
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
	<script type="text/javascript" src="Rbs/Geo/js/admin.js">​</script>
	<script type="text/javascript" src="Rbs/Geo/Zone/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Geo/Zone/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Geo/Country/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Geo/Country/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Geo/TerritorialUnit/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Geo/TerritorialUnit/editor.js">​</script>');
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
