<?php
namespace Rbs\Elasticsearch\Events\IndexManager;

use Rbs\Elasticsearch\Events\Event;
use Rbs\Elasticsearch\Services\WebsiteIndexManager;
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
		$ws = new WebsiteIndexManager();
		$events->attach(Event::INDEX_DOCUMENT, array($ws, 'onIndexDocument'));
		$events->attach(Event::ANALYZER_BY_LCID, array($ws, 'onGetAnalyzer'));
		$events->attach(Event::MAPPING_BY_NAME, array($ws, 'onGetMappingByName'));
		$events->attach(Event::POPULATE_DOCUMENT, array($ws, 'onPopulateDocument'));
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
