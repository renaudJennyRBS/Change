<?php
namespace Rbs\Elasticsearch\Job;

use Rbs\Elasticsearch\Index\IndexManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Job\Listeners
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
			$elasticsearchServices = $event->getServices('Rbs\Elasticsearch\ElasticsearchServices');
			if ($elasticsearchServices instanceof \Rbs\Elasticsearch\ElasticsearchServices)
			{
				$elasticsearchServices->getIndexManager()->dispatchIndexationEvents($event->getJob()->getArguments());
			}
			elseif($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->error(__METHOD__ . ' Elasticsearch services not registered');
			}
		};
		$events->attach('process_Elasticsearch_Index', $callback, 5);

		$callback = function(\Change\Job\Event $event)
		{
			/* TODO Update Mapping*/
		};
		$events->attach('process_Elasticsearch_Mapping', $callback, 5);
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
