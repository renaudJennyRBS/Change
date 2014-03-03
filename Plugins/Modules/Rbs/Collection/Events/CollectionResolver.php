<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Collection\Events;

use Change\Events\Event;

/**
 * @name \Rbs\Collection\Events\CollectionResolver
 */
class CollectionResolver
{
	/**
	 * @param Event $event
	 */
	public function getCollection(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$code = $event->getParam('code');
			if ($code === 'Rbs_Collection_Collection_List')
			{
				$this->getCollectionList($event);
				return;
			}
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
			$collection = $query->andPredicates($query->eq('code', $code))->getFirstDocument();
			if ($collection)
			{
				$event->setParam('collection', $collection);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function getCollectionList(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
			$qb = $query->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->addColumn($fb->alias($fb->getDocumentColumn('code'), 'code'));
			$qb->addColumn($fb->alias($fb->getDocumentColumn('label'), 'label'));
			$sq = $qb->query();
			$collectionItems = $sq->getResults($sq->getRowsConverter()->addStrCol('code', 'label')->singleColumn('label')
				->indexBy('code'));
			$collection = new \Change\Collection\CollectionArray($event->getParam('code'), $collectionItems);
			$event->setParam('collection', $collection);
		}
	}

	/**
	 * @param Event $event
	 */
	public function getCodes(Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$codes = $event->getParam('codes');
			if (!is_array($codes))
			{
				$codes = array();
			}

			$codes[] = 'Rbs_Collection_Collection_List';
			$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');
			$qb = $query->dbQueryBuilder();
			$qb->addColumn($qb->getFragmentBuilder()->alias($query->getColumn('code'), 'code'));
			foreach ($qb->query()->getResults() as $row)
			{
				$codes[] = $row['code'];
			};
			$codes = array_unique($codes);
			$event->setParam('codes', $codes);
		}
	}
}