<?php
namespace Rbs\Elasticsearch\Index;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Elasticsearch\Index\Listeners
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
		$ws = new FullTextIndexer();
		$events->attach(Event::INDEX_DOCUMENT, array($ws, 'onIndexDocument'), 5);
		$events->attach(Event::POPULATE_DOCUMENT, array($ws, 'onPopulateDocument'), 5);
		$events->attach(Event::FIND_INDEX_DEFINITION, array($ws, 'onFindIndexDefinition'), 5);
		$events->attach(Event::GET_INDEXES_DEFINITION, array($ws, 'onGetIndexesDefinition'), 5);

		$si = new StoreIndexer();
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
