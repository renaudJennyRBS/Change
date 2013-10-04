<?php
namespace Rbs\Elasticsearch\Events\JobManager;

use Rbs\Elasticsearch\Services\IndexManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Events\JobManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function(\Change\Job\Event $event)
		{
			$im = new IndexManager();
			$im->setApplicationServices($event->getApplicationServices());
			$im->setDocumentServices($event->getDocumentServices());
			$im->dispatchIndexationEvents($event->getJob()->getArguments());
		};
		$events->attach('process_Elasticsearch_Index', $callback, 5);
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
