<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Shipping\Collection;

/**
 * @name \Rbs\Shipping\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addShippingModes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices)
		{
			$docQuery = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Shipping_Mode');

			$qb = $docQuery->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$query = $qb->addColumn($fb->alias($docQuery->getColumn('id'), 'id'))
				->addColumn($fb->alias($docQuery->getColumn('label'), 'label'))->query();

			$items = array();
			foreach ($query->getResults() as $row)
			{
				$items[$row['id']] = $row['label'];
			}
			$collection = new \Change\Collection\CollectionArray('Rbs_Shipping_Collection_ShippingModes', $items);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}