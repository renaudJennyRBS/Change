<?php
namespace Change\Catalog\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Admin\Event;
/**
 * @name \Change\Catalog\Admin\Register
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
			$header =  array('<link href="Change/Catalog/css/admin.css" rel="stylesheet"/>');
			$event->setParam('header', array_merge($event->getParam('header'), $header));

			$body = array('
	<script type="text/javascript" src="Change/Catalog/js/admin.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Category/controllers.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Category/editor.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Product/controllers.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Product/editor.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Price/controllers.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Price/editor.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Shop/controllers.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Shop/editor.js">​</script>
	<script type="text/javascript" src="Change/Catalog/BillingArea/controllers.js">​</script>
	<script type="text/javascript" src="Change/Catalog/BillingArea/editor.js">​</script>
	<script type="text/javascript" src="Change/Catalog/BillingArea/taxes-section.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Currency/controllers.js">​</script>
	<script type="text/javascript" src="Change/Catalog/Currency/editor.js">​</script>');
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
