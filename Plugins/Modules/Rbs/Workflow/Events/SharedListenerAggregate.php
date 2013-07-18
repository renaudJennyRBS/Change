<?php
namespace Rbs\Workflow\Events;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Http\Event as HttpEvent;

/**
 * @name \Rbs\Workflow\Events\SharedListenerAggregate
 */
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function attachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		$callback = function (DocumentEvent $event)
		{
			(new \Rbs\Workflow\Tasks\PublicationProcess\Start())->execute($event);
		};
		$events->attach('Documents', DocumentEvent::EVENT_CREATED, $callback, 5);

		$callback = function (DocumentEvent $event)
		{
			(new \Rbs\Workflow\Tasks\CorrectionPublicationProcess\Start())->execute($event);
		};
		$events->attach('Documents', DocumentEvent::EVENT_CORRECTION_CREATED, $callback, 5);

		$callback = function (DocumentEvent $event)
		{
			(new \Rbs\Workflow\Tasks\PublicationProcess\Rest())->addTasks($event);
		};
		$events->attach('Documents', 'updateRestResult', $callback, 5);

		$callback = function (HttpEvent $event)
		{
			(new \Rbs\Workflow\Tasks\PublicationProcess\Rest())->resolveTaskExecute($event);
		};
		$events->attach('Http.Rest', 'http.action', $callback, 5);

		$callBack = function ($event)
		{
			(new \Rbs\Workflow\Job\ExecuteDeadLineTask())->execute($event);
		};
		$events->attach('JobManager', 'process_Rbs_Workflow_ExecuteDeadLineTask', $callBack, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param \Zend\EventManager\SharedEventManagerInterface $events
	 */
	public function detachShared(\Zend\EventManager\SharedEventManagerInterface $events)
	{
		//TODO
	}
}