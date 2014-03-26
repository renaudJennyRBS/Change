<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Events;

/**
* @name \Rbs\Catalog\Events\ItemOrdering
*/
class ItemOrdering
{
	/**
	 * @var \Rbs\Store\Documents\WebStore[]
	 */
	protected $stores;

	/**
	 * @var array
	 */
	protected $thresholds;


	/**
	 * @var array
	 */
	protected $cacheByProduct = [];

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onUpdateProductListItem($event)
	{
		$productListItem = $event->getParam('productListItem');
		$commerceServices = $event->getServices('commerceServices');
		$applicationServices = $event->getApplicationServices();
		if ($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem
			&& $commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$documentManager = $applicationServices->getDocumentManager();
			$dbProvider = $applicationServices->getDbProvider();
			if ($this->stores === null) {
				$this->stores = $documentManager->getNewQuery('Rbs_Store_WebStore')->getDocuments()->toArray();
			}
			if ($this->thresholds === null)
			{
				$this->thresholds = [];
				$collection = $applicationServices->getCollectionManager()->getCollection('Rbs_Stock_Collection_Threshold');
				if ($collection)
				{
					foreach ($collection->getItems() as $idx => $item)
					{
						$this->thresholds[$item->getValue()] = $idx;
					}
				}
			}


			$oldOrderings = $this->getCurrentOrdering($productListItem->getId(), $applicationServices->getDbProvider());
			$orderings = [];
			$product = $productListItem->getProduct();

			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				if (isset($this->cacheByProduct[$product->getId()]))
				{
					$orderings = $this->cacheByProduct[$product->getId()];
				}
				else
				{
					$sku = $product->getSku();
					$sortDate = $product->getRefLocalization()->getCreationDate();
					if (!$sku)
					{
						$skuArray = $commerceServices->getCatalogManager()->getAllSku($product, true);
					}
					else
					{
						$skuArray = null;
					}

					foreach ($this->stores as $store)
					{
						$defaultOrdering = ['store_id' => $store->getId(), 'sort_date' => $sortDate, 'sort_level' => null];
						if ($sku)
						{
							$threshold = $commerceServices->getStockManager()->getInventoryThreshold($sku, $store);
							$defaultOrdering['sort_level'] = isset($this->thresholds[$threshold]) ? $this->thresholds[$threshold] : null;
						}
						elseif (is_array($skuArray))
						{
							foreach ($skuArray as $subSku)
							{
								$threshold = $commerceServices->getStockManager()->getInventoryThreshold($subSku, $store);
								$thresholdIdx = isset($this->thresholds[$threshold]) ? $this->thresholds[$threshold] : null;
								if ($thresholdIdx !== null) {
									if ($defaultOrdering['sort_level'] === null || $thresholdIdx < $defaultOrdering['sort_level'])
									{
										$defaultOrdering['sort_level'] = $thresholdIdx;
									}
								}
							}
						}

						foreach ($store->getBillingAreas() as $billingArea)
						{
							$priceOptions = ['webStore' => $store, 'billingArea'=> $billingArea];
							$ordering = ['billing_area_id'=> $billingArea->getId(), 'sort_price'=> null] + $defaultOrdering;
							if ($sku)
							{
								$price = $commerceServices->getPriceManager()->getPriceBySku($sku, $priceOptions);
								$ordering['sort_price'] = $price ? $price->getValue() : null;
							}
							elseif (is_array($skuArray))
							{
								foreach ($skuArray as $subSku)
								{
									$price = $commerceServices->getPriceManager()->getPriceBySku($subSku, $priceOptions);
									$priceValue = $price ? $price->getValue() : null;
									if ($priceValue !== null) {
										if ($ordering['sort_price'] === null || $priceValue < $ordering['sort_price'])
										{
											$ordering['sort_price'] = $priceValue;
										}
									}
								}
							}
							$orderings[$store->getId() . '_' . $billingArea->getId()] = $ordering;
						}
					}
					$this->cacheByProduct = [$product->getId() => $orderings];
				}

				if (count($oldOrderings) || count($orderings))
				{
					$tm = $applicationServices->getTransactionManager();
					try
					{
						$tm->begin();
						foreach ($orderings as $key => $ordering)
						{
							if (isset($oldOrderings[$key]))
							{
								$oldOrdering = $oldOrderings[$key];
								unset($oldOrderings[$key]);

								if ($oldOrdering['sort_price'] === $ordering['sort_price']
									&& $oldOrdering['sort_level'] === $ordering['sort_level']
									&& $oldOrdering['sort_date'] == $ordering['sort_date'])
								{
									continue;
								}
							}
							$this->updateOrdering($productListItem->getId(), $ordering, $dbProvider);
						}

						if (count($oldOrderings))
						{
							if (count($orderings))
							{
								foreach ($oldOrderings as $ordering)
								{
									$this->deleteOrdering($productListItem->getId(), $ordering, $dbProvider);
								}
							}
							else
							{
								$this->deleteCurrentOrdering($productListItem->getId(), $dbProvider);
							}
						}

						$tm->commit();
					}
					catch (\Exception $e)
					{
						throw $tm->rollBack($e);
					}
				}
			}
		}
	}

	/**
	 * @param integer $itemId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return array
	 */
	protected function getCurrentOrdering($itemId, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder('ItemOrdering_Current');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('store_id'), $fb->column('billing_area_id'),
				$fb->column('sort_date'),$fb->column('sort_level'),$fb->column('sort_price'));
			$qb->from($fb->table('rbs_catalog_dat_productlistitem'));
			$qb->where($fb->eq($fb->column('listitem_id'), $fb->integerParameter('itemId')));
		}

		$query = $qb->query();
		$query->bindParameter('itemId', $itemId);
		$rows = $query->getResults($query->getRowsConverter()->addIntCol('store_id', 'billing_area_id', 'sort_level')
			->addDtCol('sort_date')->addNumCol('sort_price'));

		$orderings = [];
		foreach ($rows as $row)
		{
			$orderings[$row['store_id'] . '_' . $row['billing_area_id']] = $row;
		}
		return $orderings;
	}

	/**
	 * @param integer $itemId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return array
	 */
	protected function deleteCurrentOrdering($itemId, $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->delete($fb->table('rbs_catalog_dat_productlistitem'));
		$qb->where($fb->eq($fb->column('listitem_id'), $fb->number($itemId)));
		$qb->deleteQuery()->execute();
	}

	/**
	 * @param integer $itemId
	 * @param array $ordering
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function deleteOrdering($itemId, $ordering, $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('ItemOrdering_deleteOrdering');

		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_catalog_dat_productlistitem'));
			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('listitem_id'), $fb->integerParameter('itemId')),
					$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId')),
					$fb->eq($fb->column('billing_area_id'), $fb->integerParameter('billingAreaId'))
				));
		}

		$delete = $qb->deleteQuery();
		$delete->bindParameter('itemId', $itemId);
		$delete->bindParameter('storeId', $ordering['store_id']);
		$delete->bindParameter('billingAreaId', $ordering['billing_area_id']);
		$delete->execute();
	}

	/**
	 * @param integer $itemId
	 * @param array $ordering
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function insertOrdering($itemId, $ordering, $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('ItemOrdering_insertOrdering');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_catalog_dat_productlistitem'));
			$qb->addColumns($fb->column('listitem_id'), $fb->column('store_id'), $fb->column('billing_area_id'));
			$qb->addColumns($fb->column('sort_date'), $fb->column('sort_level'), $fb->column('sort_price'));
			$qb->addValues($fb->integerParameter('itemId'), $fb->integerParameter('storeId'), $fb->integerParameter('billingAreaId'));
			$qb->addValues($fb->dateTimeParameter('sortDate'), $fb->integerParameter('sortLevel'), $fb->decimalParameter('sortPrice'));
		}

		$insert = $qb->insertQuery();
		$insert->bindParameter('itemId', $itemId);
		$insert->bindParameter('storeId', $ordering['store_id']);
		$insert->bindParameter('billingAreaId', $ordering['billing_area_id']);
		$insert->bindParameter('sortDate', $ordering['sort_date']);
		$insert->bindParameter('sortLevel', $ordering['sort_level']);
		$insert->bindParameter('sortPrice', $ordering['sort_price']);
		$insert->execute();
	}

	/**
	 * @param integer $itemId
	 * @param array $ordering
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function updateOrdering($itemId, $ordering, $dbProvider)
	{
		$this->deleteOrdering($itemId, $ordering, $dbProvider);
		$this->insertOrdering($itemId, $ordering, $dbProvider);
	}
} 