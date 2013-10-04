<?php
namespace Rbs\Elasticsearch\Events;

/**
* @name \Rbs\Elasticsearch\Events\Event
*/
class Event extends \Zend\EventManager\Event
{
	const INDEX_DOCUMENT = 'indexDocument';
	const MAPPING_BY_NAME = 'mappingByName';
	const ANALYZER_BY_LCID = 'analyzerByLCID';

	const POPULATE_DOCUMENT = 'populateDocument';

	/**
	 * @return \Rbs\Elasticsearch\Services\IndexManager
	 */
	public function getIndexManager()
	{
		return $this->getTarget();
	}
}