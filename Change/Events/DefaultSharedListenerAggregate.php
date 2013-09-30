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
			(new \Change\Documents\Events\ValidateListener())->onValidate($event);
		};
		$events->attach($identifiers, array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onDelete($event);
		};
		$events->attach($identifiers, DocumentEvent::EVENT_DELETE, $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onDeleted($event);
		};
		$events->attach($identifiers, DocumentEvent::EVENT_DELETED, $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onLocalizedDeleted($event);
		};
		$events->attach($identifiers, DocumentEvent::EVENT_LOCALIZED_DELETED, $callBack, 5);

		$callBack = function ($event)
		{
			(new \Change\Documents\Events\DeleteListener())->onCleanUp($event);
		};
		$events->attach('JobManager', 'process_Change_Document_CleanUp', $callBack, 5);

	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
	}
}
