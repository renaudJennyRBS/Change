<?php
namespace Change\Events;

use Change\Documents\Events\Event as DocumentEvent;
use Zend\EventManager\SharedEventManagerInterface;

use Zend\EventManager\SharedListenerAggregateInterface;

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
			if ($event instanceof \Change\Documents\Events\Event)
			{
				$event->getDocument()->onDefaultUpdateRestResult($event);
			}
		};
		$events->attach($identifiers, 'updateRestResult', $callBack, 5);

		$callBack = function ($event)
		{
			if ($event instanceof \Change\Documents\Events\Event)
			{
				$event->getDocument()->onDefaultCorrectionFiled($event);
			}
		};
		$events->attach($identifiers, 'correctionFiled', $callBack, 5);


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
