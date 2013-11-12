<?php
namespace Rbs\Elasticsearch\Events;

use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Events\SharedListeners
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
		$callback = function ($event)
		{
			if ($event instanceof \Change\Documents\Events\Event)
			{
				$document = $event->getDocument();
				$application = $event->getApplication();
				$toIndex = $application->getContext()->get('elasticsearch_toIndex');
				if ($toIndex)
				{
					$deleted = ($event->getName() == 'documents.deleted' || $event->getName() == 'documents.localized.deleted');
					$toIndex[] = ['LCID' => $event->getApplicationServices()->getDocumentManager()->getLCID(),
						'id' => $document->getId(),
						'model' => $document->getDocumentModelName(), 'deleted' => $deleted];
				}
			}
		};

		$eventNames = array('documents.created', 'documents.localized.created', 'documents.updated',
			'documents.deleted', 'documents.localized.deleted');
		$events->attach('Documents', $eventNames, $callback, 5);

		$callback = function (\Change\Events\Event $event)
		{
			if ($event->getParam('primary'))
			{
				$event->getApplication()->getContext()->set('elasticsearch_toIndex', new \ArrayObject());
			}
		};
		$events->attach('TransactionManager', 'begin', $callback);

		$callback = function (\Change\Events\Event $event)
		{
			if ($event instanceof \Change\Events\Event && $event->getParam('primary'))
			{

				$application = $event->getApplication();
				/* @var $toIndex \ArrayObject */
				$toIndex = $application->getContext()->get('elasticsearch_toIndex');
				if ($toIndex)
				{
					if (count($toIndex))
					{
						/* @var $transactionManager \Change\Transaction\TransactionManager */
						$jobManager = $event->getApplicationServices()->getJobManager();
						$jobManager->createNewJob('Elasticsearch_Index', $toIndex->getArrayCopy(), null, false);
					}
					$application->getContext()->set('elasticsearch_toIndex', null);
				}
			}
		};
		$events->attach('TransactionManager', 'commit', $callback, 10);

		$callback = function ($event){
			if (($event instanceof \Change\Events\Event) &&
				($eventManagerFactory = $event->getParam('eventManagerFactory')) instanceof \Change\Events\EventManagerFactory)
			{
				$elasticsearchServices = new \Rbs\Elasticsearch\ElasticsearchServices($event->getApplication(), $eventManagerFactory, $event->getApplicationServices());
				$event->getServices()->set('Rbs\Elasticsearch\ElasticsearchServices', $elasticsearchServices);
			}
		};
		$events->attach(array('Commands', 'JobManager', 'Http.Web', 'Http.Rest'), 'registerServices', $callback, 1);
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