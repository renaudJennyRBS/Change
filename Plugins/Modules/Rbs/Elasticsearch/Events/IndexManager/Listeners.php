<?php
namespace Rbs\Elasticsearch\Events\IndexManager;

use Rbs\Elasticsearch\Events\Event;
use Rbs\Elasticsearch\Services\FullTextManager;
use Rbs\Elasticsearch\Services\StoreIndexManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Events\IndexManager\Listeners
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
		$ws = new FullTextManager();
		$events->attach(Event::INDEX_DOCUMENT, array($ws, 'onIndexDocument'), 5);
		$events->attach(Event::POPULATE_DOCUMENT, array($ws, 'onPopulateDocument'), 5);
		$events->attach(Event::FIND_INDEX_DEFINITION, array($ws, 'onFindIndexDefinition'), 5);
		$events->attach(Event::GET_INDEXES_DEFINITION, array($ws, 'onGetIndexesDefinition'), 5);

		$si = new StoreIndexManager();
		$events->attach(Event::INDEX_DOCUMENT, array($si, 'onIndexDocument'), 1);
		$events->attach(Event::POPULATE_DOCUMENT, array($si, 'onPopulateDocument'), 1);
		$events->attach(Event::FIND_INDEX_DEFINITION, array($si, 'onFindIndexDefinition'), 1);
		$events->attach(Event::GET_INDEXES_DEFINITION, array($si, 'onGetIndexesDefinition'), 1);
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
