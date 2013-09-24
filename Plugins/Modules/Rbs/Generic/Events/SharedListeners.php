<?php
namespace Rbs\Generic\Events;

use Change\Documents\Events\Event as DocumentEvent;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\SharedListeners
 */
class SharedListeners implements SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$callback = function (DocumentEvent $event)
		{
			$website = $event->getDocument();
			if ($website instanceof \Rbs\Website\Documents\Website)
			{
				(new \Rbs\Website\Events\WebsiteResolver())->changed($website);
			}
		};
		$eventNames = array(DocumentEvent::EVENT_CREATED, DocumentEvent::EVENT_UPDATED);
		$events->attach('Rbs_Website_Website', $eventNames, $callback, 5);

		$callback = function (DocumentEvent $event)
		{
			(new \Rbs\Website\Events\PageResolver())->resolve($event);
		};
		$events->attach('Documents', DocumentEvent::EVENT_DISPLAY_PAGE, $callback, 5);

		$callback = function (DocumentEvent $event)
		{
			if ($event->getName() == DocumentEvent::EVENT_CREATED &&
				$event->getDocument() instanceof \Change\Documents\Interfaces\Localizable)
			{
				return;
			}
			(new \Rbs\Workflow\Tasks\PublicationProcess\Start())->execute($event);
		};
		$events->attach('Documents', array(DocumentEvent::EVENT_CREATED, DocumentEvent::EVENT_LOCALIZED_CREATED), $callback, 5);

		$callback = function (DocumentEvent $event)
		{
			(new \Rbs\Workflow\Tasks\CorrectionPublicationProcess\Start())->execute($event);
		};
		$events->attach('Documents', DocumentEvent::EVENT_CORRECTION_CREATED, $callback, 5);

		$callback = function (DocumentEvent $event)
		{
			(new \Rbs\Workflow\Http\Rest\Actions\ExecuteTask())->addTasks($event);
		};
		$events->attach('Documents', 'updateRestResult', $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		//TODO
	}
}