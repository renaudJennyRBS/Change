<?php
namespace Rbs\Website\Events;

use Rbs\Website\Events\WebsiteResolver;
use Change\Documents\Events\Event as DocumentEvent;

/**
* @name \Rbs\Website\Events\SharedListenerAggregate
*/
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 *
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		$callback = function (\Change\Http\Web\Event $event)
		{
			$resolver = new WebsiteResolver();
			return $resolver->resolve($event);
		};
		$events->attach('Http.Web', \Change\Http\Event::EVENT_REQUEST, $callback, 5);

		$callback = function (\Change\Documents\Events\Event $event)
		{
			$website = $event->getDocument();
			if ($website instanceof \Rbs\Website\Documents\Website)
			{
				$resolver = new WebsiteResolver();
				return $resolver->changed($website);
			}
		};

		$eventNames = array(DocumentEvent::EVENT_CREATED, DocumentEvent::EVENT_UPDATED);
		$events->attach('Rbs_Website_Website', $eventNames, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 *
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function detachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		//TODO
	}
}