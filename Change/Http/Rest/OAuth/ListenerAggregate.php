<?php
namespace Change\Http\Rest\OAuth;

use Change\Http\Event as HttpEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Change\Http\Rest\OAuth\ListenerAggregate
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
		$callBack = function ($event)
		{
			$l = new AuthenticationListener();
			$l->onRequest($event);
		};
		$events->attach(array(HttpEvent::EVENT_REQUEST), $callBack, 5);

		$callBack = function ($event)
		{
			$l = new AuthenticationListener();
			$l->onResponse($event);
		};
		$events->attach(array(HttpEvent::EVENT_RESPONSE), $callBack, 10);

		$callBack = function (HttpEvent $event)
		{
			$l = new AuthenticationListener();
			try
			{
				$l->onAuthenticate($event);
			}
			catch (\RuntimeException $e)
			{
				$event->getApplicationServices()->getLogging()->exception($e);
			}
		};
		$events->attach(array(HttpEvent::EVENT_AUTHENTICATE), $callBack, 10);
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
