<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Job;

/**
 * @name \Rbs\Stock\Job\UpdateProductAvailability
 */
class UpdateProductAvailability
{
	/**
	 * @param \Change\Job\Event $event
	 * @throws \Exception
	 */
	public function execute(\Change\Job\Event $event)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$inventoryEntryId = $event->getJob()->getArgument('inventoryEntryId');
		$inventoryEntry = $documentManager->getDocumentInstance($inventoryEntryId, 'Rbs_Stock_InventoryEntry');
		if ($inventoryEntry instanceof \Rbs\Stock\Documents\InventoryEntry)
		{
			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$transactionManager = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$dbProvider = $event->getApplicationServices()->getDbProvider();

				$this->updateByInventoryEntry($inventoryEntry, $documentManager, $commerceServices->getCatalogManager(), $dbProvider);

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				$transactionManager->rollBack($e);
				$event->failed($e->getMessage());
			}
			return;
		}
		$productId = $event->getJob()->getArgument('productId');

		$product = $documentManager->getDocumentInstance($productId, 'Rbs_Catalog_Product');
		if ($product instanceof \Rbs\Catalog\Documents\Product)
		{
			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$transactionManager = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				$dbProvider = $event->getApplicationServices()->getDbProvider();
				$catalogManager = $commerceServices->getCatalogManager();
				if ($product->getSkuId())
				{

					$inventoryEntries = $commerceServices->getStockManager()->getInventoryEntries($product->getSkuId());
					foreach ($inventoryEntries as $inventoryEntry)
					{
						$this->updateByInventoryEntry($inventoryEntry, $documentManager, $catalogManager, $dbProvider);
					}
				}
				elseif ($product->getVariantGroup())
				{
					$variantGroup = $product->getVariantGroup();
					foreach ($commerceServices->getStockManager()->getAvailableWarehouseIds() as $warehouseId)
					{
						$this->updateVariantGroupAvailability($variantGroup, $warehouseId, $documentManager, $catalogManager, $dbProvider);
					}
				}
				elseif ($product->getProductSet())
				{
					foreach ($commerceServices->getStockManager()->getAvailableWarehouseIds() as $warehouseId)
					{
						$this->updateProductSetAvailability($product->getProductSet(), $warehouseId, $documentManager, $dbProvider);
					}
				}
				else
				{
					$oldSkuId = intval($event->getJob()->getArgument('oldSkuId'));
					$this->deleteAvailabilityEntry($product->getId(), $oldSkuId, $dbProvider);
					foreach ($commerceServices->getStockManager()->getAvailableWarehouseIds() as $warehouseId)
					{
						$this->updateProductSetAvailabilityByProduct($product, $warehouseId, $documentManager, $catalogManager, $dbProvider);
					}
				}

				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				$transactionManager->rollBack($e);
				$event->failed($e->getMessage());
			}
			return;
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onInventoryEntryChange(\Change\Documents\Events\Event $event)
	{
		$inventoryEntry = $event->getDocument();
		if ($inventoryEntry instanceof \Rbs\Stock\Documents\InventoryEntry)
		{
			/** @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');

			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$this->updateByInventoryEntry($inventoryEntry, $documentManager, $commerceServices->getCatalogManager(), $dbProvider);
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onProductSkuChange(\Change\Documents\Events\Event $event)
	{
		$product = $event->getDocument();
		if ($product instanceof \Rbs\Catalog\Documents\Product)
		{
			if ($product->isPropertyModified('sku'))
			{
				$oldSkuId = $product->getSkuOldValueId();
				$event->getApplicationServices()->getJobManager()->createNewJob('Rbs_Stock_UpdateProductAvailability',
					['productId' => $product->getId(), 'oldSkuId' => $oldSkuId], null, false);
			}
		}
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param \Rbs\Stock\Documents\InventoryEntry $inventoryEntry
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\CatalogManager $catalogManager
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function updateByInventoryEntry(\Rbs\Stock\Documents\InventoryEntry $inventoryEntry,
		\Change\Documents\DocumentManager $documentManager, \Rbs\Catalog\CatalogManager $catalogManager,
		\Change\Db\DbProvider  $dbProvider)
	{
		$sku = $inventoryEntry->getSku();

		if ($sku instanceof \Rbs\Stock\Documents\Sku)
		{
			$skuId = $sku->getId();
			$warehouseId = $inventoryEntry->getWarehouseId();
			$availability = $inventoryEntry->getLevel() + $inventoryEntry->getValueOfMovements();

			$products = $this->getStrictProductsBySku($sku, $documentManager);

			/** @var $product \Rbs\Catalog\Documents\Product */
			foreach ($products as $product)
			{
				$productId = $product->getId();
				$this->saveAvailabilityEntry($productId, $warehouseId, $skuId, $availability, $dbProvider);
				if ($product->getVariantGroup())
				{
					$this->updateVariantGroupAvailability($product->getVariantGroup(), $warehouseId, $documentManager, $catalogManager, $dbProvider);
				}
				else
				{
					$this->updateProductSetAvailabilityByProduct($product, $warehouseId, $documentManager, $catalogManager, $dbProvider);
				}
			}
		}
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Change\Documents\DocumentCollection
	 */
	protected function getStrictProductsBySku($sku, \Change\Documents\DocumentManager $documentManager)
	{
		$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
		$query->andPredicates($query->eq('sku', $sku));
		return $query->getDocuments();
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param integer $warehouseId
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\CatalogManager $catalogManager
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function updateVariantGroupAvailability(\Rbs\Catalog\Documents\VariantGroup $variantGroup, $warehouseId,
		\Change\Documents\DocumentManager $documentManager, \Rbs\Catalog\CatalogManager $catalogManager,
		\Change\Db\DbProvider $dbProvider)
	{

		$rootProduct = $variantGroup->getRootProduct();
		if (!$rootProduct)
		{
			return;
		}

		$query = $documentManager->getNewQuery('Rbs_Catalog_Product');
		$query->andPredicates($query->eq('variantGroup', $variantGroup), $query->neq('id', $rootProduct->getId()));
		$variantProducts = $query->getDocuments();

		$skuIdsByProduct = [];

		/** @var $variantProduct \Rbs\Catalog\Documents\Product */
		foreach ($variantProducts as $variantProduct)
		{
			if ($variantProduct->getSkuId())
			{
				$skuIdsByProduct[$variantProduct->getId()] = $variantProduct->getSkuId();
			}
		}

		$maxEntry = null;
		if (count($skuIdsByProduct))
		{
			$maxEntry = $this->getMaxAvailabilityEntryBySku(array_values($skuIdsByProduct), $warehouseId, $documentManager);
		}

		if ($maxEntry)
		{
			$availability = $maxEntry->getLevel() + $maxEntry->getValueOfMovements();
			$selectedSkuId = $maxEntry->getSkuId();
		}
		else
		{
			$availability = 0;
			$selectedSkuId = 0;
		}
		$this->saveAvailabilityEntry($rootProduct->getId(), $warehouseId, $selectedSkuId, $availability, $dbProvider);

		foreach ($variantProducts as $variantProduct)
		{
			if (!$variantProduct->getSkuId())
			{
				$skuIs = [];
				$pIds = $catalogManager->getVariantDescendantIds($variantProduct);
				foreach ($pIds as $pid)
				{
					if (isset($skuIdsByProduct[$pid]))
					{
						$skuIs[] = $skuIdsByProduct[$pid];
					}
				}
				$maxEntry = null;
				if (count($skuIs))
				{
					$maxEntry = $this->getMaxAvailabilityEntryBySku($skuIs, $warehouseId, $documentManager);

				}
				if ($maxEntry)
				{
					$availability = $maxEntry->getLevel() + $maxEntry->getValueOfMovements();
					$selectedSkuId = $maxEntry->getSkuId();
				}
				else
				{
					$availability = 0;
					$selectedSkuId = 0;
				}

				$this->saveAvailabilityEntry($variantProduct->getId(), $warehouseId,
					$selectedSkuId, $availability, $dbProvider);
			}
		}
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $warehouseId
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Rbs\Catalog\CatalogManager $catalogManager
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function updateProductSetAvailabilityByProduct(\Rbs\Catalog\Documents\Product $product, $warehouseId,
		\Change\Documents\DocumentManager $documentManager, \Rbs\Catalog\CatalogManager $catalogManager,
		\Change\Db\DbProvider $dbProvider)
	{
		$query = $documentManager->getNewQuery('Rbs_Catalog_ProductSet');
		$query->andPredicates($query->eq('products', $product));
		$productSets = $query->getDocuments();

		/** @var \Rbs\Catalog\Documents\ProductSet $productSet */
		foreach ($productSets as $productSet)
		{
			$this->updateProductSetAvailability($productSet, $warehouseId, $documentManager, $dbProvider);
		}
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param \Rbs\Catalog\Documents\ProductSet $productSet
	 * @param integer $warehouseId
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function updateProductSetAvailability(\Rbs\Catalog\Documents\ProductSet $productSet, $warehouseId,
		\Change\Documents\DocumentManager $documentManager, \Change\Db\DbProvider $dbProvider)
	{
		$rootProduct = $productSet->getRootProduct();
		if ($rootProduct && !$rootProduct->getSku())
		{
			$skuIdToCheck = [];
			foreach ($productSet->getProducts() as $productSetProduct)
			{
				$id = $productSetProduct->getSkuId();
				if ($id)
				{
					$skuIdToCheck[] = $id;
				}
			}
			$maxEntry = null;
			if (count($skuIdToCheck))
			{
				$maxEntry = $this->getMaxAvailabilityEntryBySku($skuIdToCheck, $warehouseId, $documentManager);
			}

			if ($maxEntry)
			{
				$this->saveAvailabilityEntry($rootProduct->getId(), $warehouseId,
					$maxEntry->getSkuId(), $maxEntry->getLevel() + $maxEntry->getValueOfMovements(), $dbProvider);
			}
			else
			{
				$this->saveAvailabilityEntry($rootProduct->getId(), $warehouseId, 0, 0, $dbProvider);
			}
		}
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param integer $productId
	 * @param integer $warehouseId
	 * @param integer $skuId
	 * @param integer $availability
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	public function saveAvailabilityEntry($productId, $warehouseId, $skuId, $availability, \Change\Db\DbProvider $dbProvider)
	{
		$entry = $this->getAvailabilityEntry($productId, $warehouseId, $dbProvider);
		if (is_array($entry))
		{
			if ($entry['skuId'] != $skuId || $entry['availability'] != $availability)
			{
				$this->updateAvailabilityEntry($productId, $warehouseId, $skuId, $availability, $dbProvider);
			}
		}
		else
		{
			$this->insertAvailabilityEntry($productId, $warehouseId, $skuId, $availability, $dbProvider);
		}
	}

	protected function getAvailabilityEntry($productId, $warehouseId, \Change\Db\DbProvider $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder('getAvailabilityEntry');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('sku_id'), 'skuId'),
				$fb->alias($fb->column('availability'), 'availability'));
			$qb->from($fb->table('rbs_stock_dat_availability'));
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId'))
			);
			$qb->where($logicAnd);
		}
		$query = $qb->query();
		$query->bindParameter('productId', $productId);
		$query->bindParameter('warehouseId', $warehouseId);
		return $query->getFirstResult($query->getRowsConverter()->addIntCol('skuId', 'availability'));
	}

	protected function insertAvailabilityEntry($productId, $warehouseId, $skuId, $availability, \Change\Db\DbProvider $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('insertAvailabilityEntry');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_availability'),
				$fb->column('product_id'), $fb->column('warehouse_id'),
				$fb->column('sku_id'), $fb->column('availability'), $fb->column('date'));
			$qb->addValues(
				$fb->integerParameter('productId'), $fb->integerParameter('warehouseId'),
				$fb->integerParameter('skuId'), $fb->integerParameter('availability'), $fb->dateTimeParameter('date'));
		}
		$insert = $qb->insertQuery();
		$insert->bindParameter('productId', $productId)
			->bindParameter('warehouseId', $warehouseId)
			->bindParameter('skuId', $skuId)
			->bindParameter('availability', $availability)
			->bindParameter('date', new \DateTime());
		$insert->execute();
	}

	protected function updateAvailabilityEntry($productId, $warehouseId, $skuId, $availability, \Change\Db\DbProvider $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('updateAvailabilityEntry');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_stock_dat_availability'));
			$qb->assign($fb->column('sku_id'), $fb->integerParameter('skuId'));
			$qb->assign($fb->column('availability'), $fb->integerParameter('availability'));
			$qb->assign($fb->column('date'), $fb->dateTimeParameter('date'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
					$fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId'))
				)
			);
		}
		$update = $qb->updateQuery();
		$update->bindParameter('skuId', $skuId)
			->bindParameter('availability', $availability)
			->bindParameter('date', new \DateTime())
			->bindParameter('productId', $productId)
			->bindParameter('warehouseId', $warehouseId);
		$update->execute();
	}

	/**
	 * @param integer $productId
	 * @param integer $skuId
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function deleteAvailabilityEntry($productId, $skuId, \Change\Db\DbProvider $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('deleteAvailabilityEntry');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_stock_dat_availability'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
					$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
				)
			);
		}
		$delete = $qb->deleteQuery();
		$delete->bindParameter('productId', $productId)
			->bindParameter('skuId', $skuId);
		$delete->execute();
	}


	/**
	 * @param integer[] $skuIds
	 * @param integer $warehouseId
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Rbs\Stock\Documents\InventoryEntry|null
	 */
	protected function getMaxAvailabilityEntryBySku(array $skuIds, $warehouseId, \Change\Documents\DocumentManager $documentManager)
	{
		$query = $documentManager->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->eq('warehouse', $warehouseId), $query->in('sku', $skuIds));
		$entries = $query->getDocuments()->toArray();
		if (count($entries))
		{
			usort($entries, function(\Rbs\Stock\Documents\InventoryEntry $a, \Rbs\Stock\Documents\InventoryEntry $b) {
				$va = $a->getLevel() + $a->getValueOfMovements();
				$vb = $b->getLevel() + $b->getValueOfMovements();
				return ($va == $vb) ? 0 : (($va > $vb) ? -1 : 1);
			});
			return $entries[0];
		}
		return null;
	}
}