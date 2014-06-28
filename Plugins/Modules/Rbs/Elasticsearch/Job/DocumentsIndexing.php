<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Job;

/**
* @name \Rbs\Elasticsearch\Job\DocumentsIndexing
*/
class DocumentsIndexing
{
	public function execute(\Change\Job\Event  $event)
	{
		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$genericServices->getIndexManager()->documentsBulkIndex($event->getJob()->getArguments());
		}
		else
		{
			$event->getApplication()->getLogging()->error(__METHOD__ . ' Elasticsearch services not registered');
		}
	}
} 