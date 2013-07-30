<?php
namespace Rbs\Commerce\Http\Web;

use Rbs\Commerce\Services\CommerceServices;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Change\Http\Web\Event;

/**
 * @name \Rbs\Commerce\Web\ListenerAggregate
 */
class ListenerAggregate implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$commerceServices = new CommerceServices($event->getApplicationServices(), $event->getDocumentServices());
			$event->setParam('commerceServices', $commerceServices);
			$extension = new \Rbs\Commerce\Presentation\TwigExtension($commerceServices);
			$event->getPresentationServices()->getTemplateManager()->addExtension($extension);
		};
		$events->attach(\Change\Http\Event::EVENT_REQUEST, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}