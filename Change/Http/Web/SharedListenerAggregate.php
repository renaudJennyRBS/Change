<?php
namespace Change\Http\Web;

use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Change\Http\Event as HttpEvent;

/**
 * @name \Change\Http\Web\SharedListenerAggregate
 */
class SharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$identifiers = array('Http.Web');
		$callBack = function($event) {
			$l = new AuthenticationListener();
			$l->onResponse($event);
		};
		$events->attach($identifiers, array(HttpEvent::EVENT_RESPONSE), $callBack, 10);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{

	}
}
