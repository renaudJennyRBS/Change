<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Index;

/**
* @name \Rbs\Elasticsearch\Index\ProductData
*/
class ProductData
{
	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Catalog\CatalogManager
	 */
	protected $catalogManager;

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var \Rbs\Stock\StockManager
	 */
	protected $stockManager;


	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Rbs\Catalog\CatalogManager $catalogManager
	 * @return $this
	 */
	public function setCatalogManager($catalogManager)
	{
		$this->catalogManager = $catalogManager;
		return $this;
	}

	/**
	 * @return \Rbs\Catalog\CatalogManager
	 */
	protected function getCatalogManager()
	{
		return $this->catalogManager;
	}

	/**
	 * @param \Rbs\Price\PriceManager $priceManager
	 * @return $this
	 */
	public function setPriceManager($priceManager)
	{
		$this->priceManager = $priceManager;
		return $this;
	}

	/**
	 * @return \Rbs\Price\PriceManager
	 */
	protected function getPriceManager()
	{
		return $this->priceManager;
	}

	/**
	 * @param \Rbs\Stock\StockManager $stockManager
	 * @return $this
	 */
	public function setStockManager($stockManager)
	{
		$this->stockManager = $stockManager;
		return $this;
	}

	/**
	 * @return \Rbs\Stock\StockManager
	 */
	protected function getStockManager()
	{
		return $this->stockManager;
	}


	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $documentData
	 * @return array
	 */
	public function addListItems($product, array $documentData)
	{
		$listItems = [];
		$q = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
		$q->andPredicates($q->activated(), $q->eq('product', $product));
		/** @var $productListItem \Rbs\Catalog\Documents\ProductListItem */
		foreach ($q->getDocuments() as $productListItem)
		{
			$list = $productListItem->getProductList();
			if ($list instanceof \Rbs\Catalog\Documents\ProductList)
			{
				$listItem = ['itemId' => $productListItem->getId(), 'listId' => $list->getId(),
					'position' => $productListItem->getPosition()];
				$creationDate = $productListItem->getCreationDate();
				if ($creationDate instanceof \DateTime)
				{
					$listItem['creationDate'] = $creationDate->format(\DateTime::ISO8601);
				}

				if ($list instanceof \Rbs\Catalog\Documents\SectionProductList)
				{
					$listItem['sectionId'] = $list->getSynchronizedSectionId();
				}
				$listItems[] = $listItem;
			}
		}
		$documentData['listItems'] = $listItems;
		return $documentData;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return \Rbs\Stock\Documents\Sku[]
	 */
	public function getSkuArrayByProduct($product)
	{
		$skuArray = [];
		if ($product->getSku())
		{
			$skuArray[] = $product->getSku();
			return $skuArray;
		}
		else
		{
			$skuArray = $this->getCatalogManager()->getAllSku($product, true);
			return $skuArray;
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Stock\Documents\Sku[] $skuArray
	 * @param array $documentData
	 * @return array
	 */
	public function addPrices($product, array $skuArray, array $documentData)
	{
		$prices = [];
		if (count($skuArray) > 0)
		{
			$priceManager = $this->getPriceManager();
			$q = $this->getDocumentManager()->getNewQuery('Rbs_Price_Price');
			$q->andPredicates($q->eq('active', true), $q->in('sku', $skuArray), $q->eq('targetId', 0));
			$q->addOrder('webStore');
			$q->addOrder('billingArea');
			$q->addOrder('priority', false);
			if (count($skuArray) > 1)
			{
				$q->addOrder('value', true);
			}
			$q->addOrder('startActivation', false);

			$billingAreaId = null;
			$storeId = null;
			$startActivation = null;
			$zones = null;
			$now = new \DateTime();

			/** @var $price \Rbs\Price\Documents\Price */
			foreach ($q->getDocuments() as $price)
			{
				$priceValue = $price->getValue();
				$billingArea = $price->getBillingArea();
				$store = $price->getWebStore();
				$startActivation = $price->getStartActivation();
				$endActivation = $price->getEndActivation();
				if ($priceValue === null || !$billingArea || !$store || !$startActivation || ($endActivation && $endActivation < $now ))
				{
					continue;
				}

				if ($billingAreaId != $price->getBillingAreaId() || $storeId != $price->getWebStoreId())
				{
					$billingAreaId = $price->getBillingAreaId();
					$storeId = $price->getWebStoreId();
					if (!($endActivation instanceof \DateTime))
					{
						$endActivation = (new \DateTime())->add(new \DateInterval('P10Y'));
					}

					$zones = [];
					foreach ($billingArea->getTaxes() as $tax)
					{
						$zones = array_merge($zones, $tax->getZoneCodes());
					}
					$zones = array_unique($zones);
				}
				else
				{
					$endActivation = $startActivation;
				}

				if ($endActivation < $now)
				{
					continue;
				}
				$startActivation = $price->getStartActivation();

				$priceData = ['priceId' => $price->getId(), 'billingAreaId' => $billingAreaId, 'storeId' => $storeId,
					'startActivation' => $startActivation->format(\DateTime::ISO8601),
					'endActivation' => $endActivation->format(\DateTime::ISO8601),
					'zone' => '', 'value' => $priceValue, 'valueWithTax' => $priceValue];
				$prices[] = $priceData;

				if ($zones)
				{
					$isWithTax = $price->isWithTax();
					$taxCategories = $price->getTaxCategories();
					foreach ($zones as $zone)
					{
						$priceZone = $priceData;
						$priceZone['zone'] = $zone;
						if ($isWithTax)
						{
							$taxes = $priceManager->getTaxByValueWithTax($priceValue, $taxCategories, $billingArea, $zone);
							$priceZone['valueWithTax'] = $priceManager->getValueWithoutTax($priceValue, $taxes);
						}
						else
						{
							$taxes = $priceManager->getTaxByValue($price->getValue(), $taxCategories, $billingArea, $zone);
							$priceZone['valueWithTax'] = $priceManager->getValueWithTax($priceValue, $taxes);
						}
						$prices[] = $priceZone;
					}
				}
			}
		}
		$documentData['prices'] = $prices;
		return $documentData;
	}

	/**
	 * @var array
	 */
	protected $thresholdIndexes;

	/**
	 * @return array
	 */
	protected function getThresholdIndexes()
	{
		if ($this->thresholdIndexes === null)
		{
			$this->thresholdIndexes = [];
			$query = $this->getDocumentManager()->getNewQuery('Rbs_Collection_Collection');

			/** @var $collection \Rbs\Collection\Documents\Collection */
			$collection = $query->andPredicates($query->eq('code', 'Rbs_Stock_Collection_Threshold'))->getFirstDocument();
			if ($collection)
			{
				foreach ($collection->getItems() as $index => $item)
				{
					$this->thresholdIndexes[$item->getValue()] = $index;
				}
			}

			if (!count($this->thresholdIndexes))
			{
				$this->thresholdIndexes[\Rbs\Stock\StockManager::THRESHOLD_AVAILABLE] = 0;
			}
		}
		return $this->thresholdIndexes;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Stock\Documents\Sku[] $skuArray
	 * @param array $documentData
	 * @return array
	 */
	public function addStock($product, array $skuArray, array $documentData)
	{
		$stocks = [];
		$stockManager = $this->getStockManager();

		$thresholdIndexes = $this->getThresholdIndexes();
		foreach ($stockManager->getAvailableWarehouseIds() as $warehouseId)
		{
			$threshold = \Rbs\Stock\StockManager::THRESHOLD_UNAVAILABLE;
			$maxAvailability = 0;
			$skuIds = [];
			$inventoryEntriesId = [];
			foreach ($skuArray as $sku)
			{
				$skuIds[] = $sku->getId();

				$inventoryEntry = $stockManager->getInventoryEntry($sku, $warehouseId);
				if ($inventoryEntry)
				{
					$inventoryEntriesId[] = $inventoryEntry->getId();
					$availability = $inventoryEntry->getLevel() + $inventoryEntry->getValueOfMovements();
					if ($availability > $maxAvailability)
					{
						$maxAvailability = $availability;
						$threshold = $stockManager->getInventoryThreshold($sku, null, $availability);
					}
				}
			}

			$thresholdIndex = isset($thresholdIndexes[$threshold]) ? $thresholdIndexes[$threshold] : count($thresholdIndexes);

			$stocks[] = ['skuId' => $skuIds, 'inventoryEntryId' => $inventoryEntriesId, 'warehouseId' => $warehouseId,
				'availability' => $maxAvailability, 'threshold' => $threshold, 'thresholdIndex' => $thresholdIndex];
		}
		$documentData['stocks'] = $stocks;
		return $documentData;
	}
} 