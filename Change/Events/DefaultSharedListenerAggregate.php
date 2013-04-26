<?php
namespace Change\Events;

use Zend\EventManager\SharedListenerAggregateInterface;
use Zend\EventManager\SharedEventManagerInterface;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Http\Event as HttpEvent;

/**
 * @name \Change\Events\DefaultSharedListenerAggregate
 */
class DefaultSharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$identifiers = array('Documents');

		$callBack = function ($event)
		{
			$l = new \Change\Documents\Events\ValidateListener();
			$l->onValidate($event);
		};
		$events->attach($identifiers, array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), $callBack, 5);

		$callBack = function ($event)
		{
			$l = new \Change\Documents\Events\DeleteListener();
			$l->onDelete($event);
		};
		$events->attach($identifiers, DocumentEvent::EVENT_DELETE, $callBack, 5);


		$callBack = function ($event)
		{
			$l = new \Change\Documents\Events\DeleteListener();
			$l->onDeleted($event);
		};
		$events->attach($identifiers, DocumentEvent::EVENT_DELETED, $callBack, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
	}
}
