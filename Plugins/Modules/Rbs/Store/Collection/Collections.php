<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Store\Collection;

use Change\Collection\CollectionArray;

/**
 * @name \Rbs\Store\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addWebStores(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$collection = array();
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Store_WebStore');
			$builder = $query->dbQueryBuilder();
			$fb = $builder->getFragmentBuilder();
			$builder->addColumn($fb->alias($fb->getDocumentColumn('id'), 'id'));
			$builder->addColumn($fb->alias($fb->getDocumentColumn('label'), 'label'));
			$selectQuery = $builder->query();
			$rows = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id')->addStrCol('label'));
			foreach ($rows as $row)
			{
				$collection[$row['id']] = $row['label'];
			}
			$collection = new CollectionArray('Rbs_Store_Collection_WebStores', $collection);
			$event->setParam('collection', $collection);
		}
	}
}