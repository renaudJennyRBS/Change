<?php
namespace Rbs\Elasticsearch\Events;

/**
* @name \Rbs\Elasticsearch\Events\Event
*/
class Event extends \Zend\EventManager\Event
{
	const INDEX_DOCUMENT = 'indexDocument';
	const POPULATE_DOCUMENT = 'populateDocument';
	const FIND_INDEX_DEFINITION = 'findIndexDefinition';
	const GET_FACETS_DEFINITION = 'getFacetsDefinition';

	/**
	 * @return \Rbs\Elasticsearch\Services\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->getTarget();
	}
}