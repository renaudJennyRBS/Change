<?php
namespace Rbs\Catalog\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Admin\Event;
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
	<script type="text/javascript" src="Rbs/Catalog/Product/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Product/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Price/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Price/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Shop/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Shop/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/BillingArea/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/BillingArea/editor.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/BillingArea/taxes-section.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Currency/controllers.js">​</script>
	<script type="text/javascript" src="Rbs/Catalog/Currency/editor.js">​</script>');
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
