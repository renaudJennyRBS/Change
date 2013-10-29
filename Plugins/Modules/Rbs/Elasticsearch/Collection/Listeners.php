<?php
namespace Rbs\Elasticsearch\Collection;

use Change\Collection\CollectionManager;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Collection\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			switch ($event->getParam('code'))
			{
				case 'Rbs_Elasticsearch_Collection_Clients':
					(new \Rbs\Elasticsearch\Collection\Collections())->addClients($event);
					break;
				case 'Rbs_Elasticsearch_Collection_Indexes':
					(new \Rbs\Elasticsearch\Collection\Collections())->addIndexes($event);
					break;
				case 'Rbs_Elasticsearch_Collection_CollectionCodes':
					(new \Rbs\Elasticsearch\Collection\Collections())->addCollectionCodes($event);
					break;
				case 'Rbs_Elasticsearch_Collection_AttributeIds':
					(new \Rbs\Elasticsearch\Collection\Collections())->addAttributeIds($event);
					break;
				case 'Rbs_Elasticsearch_Collection_FacetTypes':
					(new \Rbs\Elasticsearch\Collection\Collections())->addFacetTypes($event);
					break;
				case 'Rbs_Elasticsearch_Collection_FacetValueExtractor':
					(new \Rbs\Elasticsearch\Collection\Collections())->addFacetValueExtractor($event);
					break;
			}
		};
		$events->attach(CollectionManager::EVENT_GET_COLLECTION, $callback, 10);

		$callback = function (Event $event)
		{
			$codes = $event->getParam('codes', array());
			$codes[] = 'Rbs_Elasticsearch_Collection_Clients';
			$codes[] = 'Rbs_Elasticsearch_Collection_Indexes';
			$codes[] = 'Rbs_Elasticsearch_Collection_CollectionCodes';
			$codes[] = 'Rbs_Elasticsearch_Collection_AttributeIds';
			$codes[] = 'Rbs_Elasticsearch_Collection_FacetTypes';
			$codes[] = 'Rbs_Elasticsearch_Collection_FacetValueExtractor';
			$event->setParam('codes', $codes);
		};
		$events->attach(CollectionManager::EVENT_GET_CODES, $callback, 1);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}