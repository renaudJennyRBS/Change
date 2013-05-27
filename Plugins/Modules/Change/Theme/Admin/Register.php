<?php
namespace Change\Theme\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Admin\Event;
/**
 * @name \Change\Theme\Admin\Register
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
	<script type="text/javascript" src="Change/Theme/js/admin.js">​</script>
	<script type="text/javascript" src="Change/Theme/PageTemplate/controllers.js">​</script>
	<script type="text/javascript" src="Change/Theme/PageTemplate/editor.js">​</script>
	<script type="text/javascript" src="Change/Theme/Theme/controllers.js">​</script>
	<script type="text/javascript" src="Change/Theme/Theme/editor.js">​</script>');
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
