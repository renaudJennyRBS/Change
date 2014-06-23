<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Commands;

/**
 * @name \Rbs\Stock\Commands\CheckInventoryEntries
 */
class CheckInventoryEntries
{
	/**
	 * @param \Change\Commands\Events\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Commands\Events\Event $event)
	{
		$response = $event->getCommandResponse();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			if ($response)
			{
				$response->addErrorMessage('Commerce services not set');
			}
			return;
		}

		$stockManager = $commerceServices->getStockManager();
		$refreshAvailability = $event->getParam('refreshAvailability');

		$warehouseIds = [0];

		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		$documentManager = $event->getApplicationServices()->getDocumentManager();

		foreach ($warehouseIds as $warehouseId)
		{
			if ($response)
			{
				$response->addInfoMessage('Check Warehouse id: ' . $warehouseId);
			}
			$lastSkuId = 0;
			do
			{
				if ($response)
				{
					if ($refreshAvailability)
					{
						$response->addInfoMessage('Check next 100 SKU with id > ' . $lastSkuId . ' and refresh products availability');
					}
					else
					{
						$response->addInfoMessage('Check next 100 SKU with id > ' . $lastSkuId);
					}
				}

				$query = $documentManager->getNewQuery('Rbs_Stock_Sku');
				$query->andPredicates($query->gt('id', $lastSkuId))->addOrder('id');
				$skuIds = $query->getDocumentIds(0, 100);
				$countSku = count($skuIds);

				if ($countSku)
				{
					$lastSkuId = $skuIds[$countSku - 1];
					try
					{
						$transactionManager->begin();
						$this->checkEntries($warehouseId, $skuIds, $stockManager, $documentManager, $refreshAvailability);
						$transactionManager->commit();
					}
					catch (\Exception $e)
					{
						$transactionManager->rollBack($e);
						if ($response)
						{
							$response->addErrorMessage($e->getMessage());
						}
					}
				}
			}
			while ($countSku === 100);
		}
		if ($response)
		{
			$response->addInfoMessage('Done.');
		}
	}

	public function onSkuCreated(\Change\Documents\Events\Event $event)
	{
		$sku = $event->getDocument();
		if (!($sku instanceof \Rbs\Stock\Documents\Sku))
		{
			$event->getApplication()->getLogging()->error(__METHOD__ . ': Invalid sku.');
			return;
		}
		$skuIds = [$sku->getId()];

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			$event->getApplication()->getLogging(__METHOD__ . ': Commerce services not set.');
			return;
		}

		$stockManager = $commerceServices->getStockManager();
		$warehouseIds = $stockManager->getAvailableWarehouseIds();

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		foreach ($warehouseIds as $warehouseId)
		{
			$this->checkEntries($warehouseId, $skuIds, $stockManager, $documentManager);
		}
	}

	public function onSkuUpdated(\Change\Documents\Events\Event $event)
	{
		$sku = $event->getDocument();
		if (!($sku instanceof \Rbs\Stock\Documents\Sku))
		{
			$event->getApplication()->getLogging()->error(__METHOD__ . ': Invalid sku.');
			return;
		}

		$modifiedPropertyNames = $event->getParam('modifiedPropertyNames');
		if (is_array($modifiedPropertyNames) && in_array('unlimitedInventory', $modifiedPropertyNames))
		{

			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
			{
				$event->getApplication()->getLogging(__METHOD__ . ': Commerce services not set.');
				return;
			}

			$stockManager = $commerceServices->getStockManager();
			$warehouseIds = $stockManager->getAvailableWarehouseIds();

			$documentManager = $event->getApplicationServices()->getDocumentManager();
			foreach ($warehouseIds as $warehouseId)
			{
				/** @var $warehouse \Rbs\Stock\Documents\AbstractWarehouse|null */
				$warehouse = $warehouseId > 0 ? $documentManager->getDocumentInstance($warehouseId) : null;
				$entry = $stockManager->getInventoryEntry($sku, $warehouse);
				if ($entry)
				{
					if ($sku->getUnlimitedInventory())
					{
						$entry->setUnlimited();
						$entry->save();
					}
					else
					{
						$valueOfMovements = intval($stockManager->getValueOfMovementsBySku($sku, $warehouseId));
						$entry->updateValueOfMovements($valueOfMovements);
					}
				}
			}
		}
	}

	/**
	 * @param integer $warehouseId
	 * @param integer[] $skuIds
	 * @param \Rbs\Stock\StockManager $stockManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param boolean $refreshAvailability
	 */
	public function checkEntries($warehouseId, $skuIds, \Rbs\Stock\StockManager $stockManager,
		\Change\Documents\DocumentManager $documentManager, $refreshAvailability = false)
	{
		/** @var $warehouse \Rbs\Stock\Documents\AbstractWarehouse|null */
		$warehouse = $warehouseId > 0 ? $documentManager->getDocumentInstance($warehouseId) : null;
		foreach ($skuIds as $skuId)
		{
			$inventoryEntry = $stockManager->getInventoryEntry($skuId, $warehouse);
			if (!$inventoryEntry)
			{
				$sku = $documentManager->getDocumentInstance($skuId, 'Rbs_Stock_Sku');
				if ($sku instanceof \Rbs\Stock\Documents\Sku)
				{
					$level = $sku->getUnlimitedInventory() ? \Rbs\Stock\StockManager::UNLIMITED_LEVEL : 0;

					/** @var $inventoryEntry \Rbs\Stock\Documents\InventoryEntry */
					$inventoryEntry = $documentManager->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
					$inventoryEntry->setLevel($level);
					$inventoryEntry->setSku($sku);
					$inventoryEntry->setWarehouse($warehouse);
					$inventoryEntry->save();
				}
			}

			if ($inventoryEntry instanceof \Rbs\Stock\Documents\InventoryEntry)
			{
				$valueOfMovements = intval($stockManager->getValueOfMovementsBySku($skuId, $warehouseId));
				$inventoryEntry->updateValueOfMovements($valueOfMovements, $refreshAvailability);
			}
		}
	}
}