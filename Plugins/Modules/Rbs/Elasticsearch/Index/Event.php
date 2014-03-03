<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

/**
 * @name \Rbs\Elasticsearch\Index\Event
 */
class Event extends \Change\Events\Event
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