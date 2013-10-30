<?php
namespace Rbs\Elasticsearch\Index;

/**
 * @name \Rbs\Elasticsearch\Index\Event
 */
class Event extends \Zend\EventManager\Event
{
	const INDEX_DOCUMENT = 'indexDocument';
	const POPULATE_DOCUMENT = 'populateDocument';
	const FIND_INDEX_DEFINITION = 'findIndexDefinition';
	const GET_INDEXES_DEFINITION = 'getIndexesDefinition';

	/**
	 * @return \Rbs\Elasticsearch\Index\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->getTarget();
	}
}