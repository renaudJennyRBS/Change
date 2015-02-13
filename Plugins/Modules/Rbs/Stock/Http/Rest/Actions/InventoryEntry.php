<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Http\Rest\Actions;

/**
 * @name \Rbs\Stock\Http\Rest\Actions\InventoryEntry
 */
class InventoryEntry
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function getInfos($event)
	{
		$warehouseId = intval($event->getRequest()->getQuery('warehouseId'));

		$inventoryEntryId = $event->getParam('inventoryEntryId');
		/** @var $inventoryEntry \Rbs\Stock\Documents\InventoryEntry*/
		$inventoryEntry = $event->getApplicationServices()->getDocumentManager()
			->getDocumentInstance($inventoryEntryId, 'Rbs_Stock_InventoryEntry');

		if ($inventoryEntry != null)
		{
			$result = new \Change\Http\Rest\V1\ArrayResult();
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);

			$nbMvt = null;
			$totalMvt = null;
			$nbRes = null;
			$nbConfirmedRes = null;
			$currentLevel = null;

			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$stockManager = $cs->getStockManager();

				$nbMvt = $stockManager->countInventoryMovementsBySku($inventoryEntry->getSku(), $warehouseId);
				$totalMvt = $stockManager->getValueOfMovementsBySku($inventoryEntry->getSku(), $warehouseId);

				$currentLevel = $inventoryEntry->getLevel() + $totalMvt;
			}

			$result->setArray(array('nbMovement' => $nbMvt, 'totalMovement' => $totalMvt, 'currentLevel' => $currentLevel));
		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Inventory entry ID missing.', \Zend\Http\Response::STATUS_CODE_409);
		}

		$event->setResult($result);
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function consolidateMovements($event)
	{
		$inventoryEntryId = $event->getParam('inventoryEntryId');

		/** @var $inventoryEntry \Rbs\Stock\Documents\InventoryEntry */
		$inventoryEntry = $event->getApplicationServices()->getDocumentManager()
			->getDocumentInstance($inventoryEntryId, 'Rbs_Stock_InventoryEntry');

		$cs = $event->getServices('commerceServices');
		if (!($cs instanceof \Rbs\Commerce\CommerceServices))
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Commerce services not set', \Zend\Http\Response::STATUS_CODE_409);
		}
		elseif ($inventoryEntry != null)
		{
			$transactionManager = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$result = new \Change\Http\Rest\V1\ArrayResult();
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);


				$stockManager = $cs->getStockManager();

				$transactionManager->begin();

				$stockManager->consolidateInventoryEntry($inventoryEntry);

				$nbMvt = $stockManager->countInventoryMovementsBySku($inventoryEntry->getSku(), $inventoryEntry->getWarehouseId());
				$totalMvt = $stockManager->getValueOfMovementsBySku($inventoryEntry->getSku(), $inventoryEntry->getWarehouseId());

				$currentLevel = $inventoryEntry->getLevel() + $totalMvt;
				$result->setArray(array('nbMovement' => $nbMvt, 'totalMovement' => $totalMvt, 'currentLevel' => $currentLevel, 'level' => $inventoryEntry->getLevel()));
				$transactionManager->commit();
			}
			catch(\Exception $e)
			{
				$result = new \Change\Http\Rest\V1\ErrorResult(999999,
					'An error has occurred during movements consolidation', \Zend\Http\Response::STATUS_CODE_409);
				$transactionManager->rollBack($e);
			}
		}
		else
		{
			$result = new \Change\Http\Rest\V1\ErrorResult(999999,
				'Inventory entry ID missing.', \Zend\Http\Response::STATUS_CODE_409);
		}

		$event->setResult($result);
	}
}