<?php
namespace Rbs\Elasticsearch\Events;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Job\JobManager;
use Rbs\Elasticsearch\Services\IndexManager;
use Rbs\Elasticsearch\Services\WebsiteIndexManager;
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
		$callback = function (DocumentEvent $event)
		{
			$document = $event->getDocument();
			$application = $document->getApplicationServices()->getApplication();
			$toIndex = $application->getContext()->get('elasticsearch_toIndex');
			if ($toIndex)
			{
				$deleted = ($event->getName() == DocumentEvent::EVENT_DELETED || $event->getName() == DocumentEvent::EVENT_LOCALIZED_DELETED);
				$toIndex[] = ['LCID' => $document->getDocumentManager()->getLCID(), 'id' => $document->getId(),
					'model' => $document->getDocumentModelName(), 'deleted' => $deleted];
			}
		};
		$eventNames = array(DocumentEvent::EVENT_CREATED, DocumentEvent::EVENT_LOCALIZED_CREATED,
			DocumentEvent::EVENT_UPDATED,
			DocumentEvent::EVENT_DELETED, DocumentEvent::EVENT_LOCALIZED_DELETED);
		$events->attach('Documents', $eventNames, $callback, 5);

		$callback = function(\Zend\EventManager\Event $event)
		{
			if ($event->getParam('primary'))
			{
				/** @var $tm \Change\Transaction\TransactionManager */
				$tm = $event->getTarget();
				$tm->getApplication()->getContext()->set('elasticsearch_toIndex', new \ArrayObject());
			}
		};
		$events->attach('TransactionManager', 'begin', $callback);

		$callback = function(\Zend\EventManager\Event $event)
		{
			if ($event->getParam('primary'))
			{
				/** @var $transactionManager \Change\Transaction\TransactionManager */
				$transactionManager = $event->getTarget();
				$application = $transactionManager->getApplication();

				/* @var $toIndex \ArrayObject */
				$toIndex = $application->getContext()->get('elasticsearch_toIndex');
				if ($toIndex)
				{
					if (count($toIndex))
					{
						$jobManager = $application->getContext()->get('elasticsearch_JobManager');
						if ($jobManager === null)
						{
							$as = new \Change\Application\ApplicationServices($application);
							$jobManager = new JobManager();
							$jobManager->setApplicationServices($as);
							$application->getContext()->set('elasticsearch_JobManager', $jobManager);
						}
						$jobManager->createNewJob('Elasticsearch_Index', $toIndex->getArrayCopy());
					}
					$transactionManager->getApplication()->getContext()->set('elasticsearch_toIndex', null);
				}
			}
		};
		$events->attach('TransactionManager', 'commit', $callback);
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