<?php
namespace Rbs\Catalog;

use Change\Stdlib\String;

/**
 * @name \Rbs\Catalog\CatalogManager
 */
class CatalogManager
{
	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Price\PriceManager
	 */
	protected $priceManager;

	/**
	 * @var \Rbs\Stock\StockManager
	 */
	protected $stockManager;

	/**
	 * @var \Rbs\Catalog\Attribute\AttributeManager
	 */
	protected $attributeManager;

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager($transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return $this
	 */
	public function setDbProvider($dbProvider)
	{
		$this->dbProvider = $dbProvider;
		return $this;
	}

	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->dbProvider;
	}

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
	 * @param \Rbs\Catalog\Attribute\AttributeManager $attributeManager
	 * @return $this
	 */
	public function setAttributeManager($attributeManager)
	{
		$this->attributeManager = $attributeManager;
		return $this;
	}

	/**
	 * @return \Rbs\Catalog\Attribute\AttributeManager
	 */
	protected function getAttributeManager()
	{
		return $this->attributeManager;
	}

	/**
	 * Add the product in a product list for the given condition/priority.
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @return \Rbs\Catalog\Documents\ProductListItem
	 * @throws \Exception
	 */
	public function addProductInProductList(\Rbs\Catalog\Documents\Product $product,
		\Rbs\Catalog\Documents\ProductList $productList, $condition)
	{
		$documentManager = $this->getDocumentManager();
		$tm = $this->getTransactionManager();
		$productListItem = null;
		try
		{
			$tm->begin();
			$productListItem = $this->getProductListItem($product, $productList, $condition);
			if (!$productListItem)
			{
				$productListItem = $documentManager->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductListItem');
				/* @var $productListItem \Rbs\Catalog\Documents\ProductListItem */
				$productListItem->setProduct($product);
				$productListItem->setProductList($productList);
				$productListItem->setCondition($condition);
			}
			$productListItem->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $productListItem;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param $condition
	 * @throws \Exception
	 */
	public function removeProductFromProductList(\Rbs\Catalog\Documents\Product $product,
		\Rbs\Catalog\Documents\ProductList $productList, $condition)
	{
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
			$productListItem = $this->getProductListItem($product, $productList, $condition);
			if ($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem)
			{
				if ($productListItem->isHighlighted())
				{
					$this->downplayProductInProductList($product, $productList, $condition);
				}
				$productListItem->delete();
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @return \Rbs\Catalog\Documents\ProductListItem|null
	 */
	public function getProductListItem(\Rbs\Catalog\Documents\Product $product, \Rbs\Catalog\Documents\ProductList $productList,
		$condition)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
		$query->andPredicates($query->eq('product', $product), $query->eq('productList', $productList),
			$query->eq('condition', $condition));
		return $query->getFirstDocument();
	}

	/**
	 * This method performs a bulk update, so you should not be messing with positions at all!
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @param \Rbs\Catalog\Documents\Product $before
	 * @throws \RuntimeException
	 */
	public function highlightProductInProductList(\Rbs\Catalog\Documents\Product $product,
		\Rbs\Catalog\Documents\ProductList $productList, $condition = null, $before = null)
	{
		$productListItem = $this->getProductListItem($product, $productList, $condition);
		if (!$productListItem)
		{
			throw new \RuntimeException("Product to highlight is not in list", 999999);
		}
		$beforeProductListItem = null;
		if ($before instanceof \Rbs\Catalog\Documents\Product)
		{
			$beforeProductListItem = $this->getProductListItem($before, $productList, $condition);
		}
		$this->highlightProductListItem($productListItem, $beforeProductListItem);
	}

	/**
	 * This method performs a bulk update, so you should not be messing with positions at all!
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @throws \RuntimeException
	 */
	public function downplayProductInProductList(\Rbs\Catalog\Documents\Product $product,
		\Rbs\Catalog\Documents\ProductList $productList, $condition = null)
	{
		$productListItem = $this->getProductListItem($product, $productList, $condition);
		if (!$productListItem)
		{
			throw new \RuntimeException("Product to highlight is not in list", 999999);
		}
		$currentPosition = $productListItem->getPosition();
		if ($currentPosition === 0)
		{
			// Nothing to do
			return;
		}
		$this->downplayProductListItem($productListItem);
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @throws \Exception
	 */
	public function downplayProductListItem($productListItem)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		$productList = $productListItem->getProductList();
		$condition = $productListItem->getCondition();
		$tm = $this->getTransactionManager();
		$updateQuery = $this->getDbProvider()->getNewStatementBuilder();
		$fb = $updateQuery->getFragmentBuilder();
		$positionColumn = $fb->getDocumentColumn('position');
		$conditionId = ($condition) ? $condition->getId() : 0;
		$where = $fb->logicAnd(
			$fb->lt($fb->getDocumentColumn('position'), $fb->number(0)),
			$fb->eq($fb->getDocumentColumn('productList'), $fb->number($productList->getId())),
			$fb->eq($fb->getDocumentColumn('condition'), $fb->number($conditionId))
		);

		try
		{
			$tm->begin();
			$updateQuery->update($fb->getDocumentTable($productListItem->getDocumentModel()->getRootName()));
			$updateQuery->assign($positionColumn, $fb->addition($positionColumn, $fb->number(1)));
			$updateQuery->where($fb->logicAnd($where,
				$fb->lte($positionColumn, $fb->number($productListItem->getPosition()))));
			$updateQuery->updateQuery()->execute();
			$productListItem->setPosition(0);
			$productListItem->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @param \Rbs\Catalog\Documents\ProductListItem|null $beforeProductListItem
	 * @throws \RuntimeException
	 * @throws \Exception
	 * @internal param $before
	 */
	public function highlightProductListItem($productListItem, $beforeProductListItem = null)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		$productList = $productListItem->getProductList();
		$condition = $productListItem->getCondition();
		$currentPosition = $productListItem->getPosition();
		$atPosition = -1;
		if ($beforeProductListItem instanceof \Rbs\Catalog\Documents\ProductListItem)
		{
			$beforePosition = $beforeProductListItem->getPosition();
			if ($currentPosition < 0)
			{
				// We are re-ordering
				$atPosition = $currentPosition < $beforePosition ? $beforePosition - 1 : $beforePosition;
			}
			else
			{
				// We introduce a new node
				$atPosition = $beforePosition - 1;
			}
		}
		if ($currentPosition === $atPosition)
		{
			// Nothing to do
			return;
		}
		// Prepare what's needed for queries
		$updateQuery = $this->getDbProvider()->getNewStatementBuilder();
		$fb = $updateQuery->getFragmentBuilder();
		$positionColumn = $fb->getDocumentColumn('position');
		$conditionId = ($condition) ? $condition->getId() : 0;
		$where = $fb->logicAnd(
			$fb->lt($fb->getDocumentColumn('position'), $fb->number(0)),
			$fb->eq($fb->getDocumentColumn('productList'), $fb->number($productList->getId())),
			$fb->eq($fb->getDocumentColumn('condition'), $fb->number($conditionId))
		);

		$tm = $this->getTransactionManager();
		if ($currentPosition == 0)
		{
			try
			{
				$tm->begin();
				$updateQuery->update($fb->getDocumentTable($productListItem->getDocumentModel()->getRootName()));
				$updateQuery->assign($positionColumn, $fb->subtraction($positionColumn, $fb->number(1)));
				$updateQuery->where($fb->logicAnd($where, $fb->lte($positionColumn, $fb->number($atPosition))));
				$updateQuery->updateQuery()->execute();
				$productListItem->setPosition($atPosition);
				$productListItem->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
		else
		{
			try
			{
				$tm->begin();
				$updateQuery->update($fb->getDocumentTable($productListItem->getDocumentModel()->getRootName()));
				if ($currentPosition > $atPosition)
				{
					$updateQuery->assign($positionColumn, $fb->addition($positionColumn, $fb->number(1)));
					$updateQuery->where($fb->logicAnd($where, $fb->gte($positionColumn, $fb->number($atPosition),
						$fb->lt($positionColumn, $fb->number($currentPosition)))));
				}
				else
				{
					$updateQuery->assign($positionColumn, $fb->subtraction($positionColumn, $fb->number(1)));
					$updateQuery->where($fb->logicAnd($where, $fb->lte($positionColumn, $fb->number($atPosition),
						$fb->gt($positionColumn, $fb->number($currentPosition)))));
				}
				$updateQuery->updateQuery()->execute();
				$productListItem->setPosition($atPosition);
				$productListItem->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function moveProductListItemDown($productListItem)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		if ($productListItem->getPosition() == 0)
		{
			// Nothing to do
			return;
		}
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
		$condition = $productListItem->getCondition();

		$query->andPredicates(
			$query->eq('productList', $productListItem->getProductList()),
			$query->eq('condition', $condition),
			$query->gt('position', $query->getFragmentBuilder()->number($productListItem->getPosition())),
			$query->lt('position', $query->getFragmentBuilder()->number(0))
		);

		$query->addOrder('position', true);
		/* @var $downCat \Rbs\Catalog\Documents\ProductListItem */
		$downCat = $query->getFirstDocument();
		if ($downCat)
		{
			$tm = $this->getTransactionManager();
			try
			{
				$tm->begin();
				$toPosition = $downCat->getPosition();
				$fromPosition = $productListItem->getPosition();
				$productListItem->setPosition($toPosition);
				$downCat->setPosition($fromPosition);
				$productListItem->save();
				$downCat->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function moveProductListItemUp($productListItem)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		if ($productListItem->getPosition() == 0)
		{
			// Nothing to do
			return;
		}
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
		$condition = $productListItem->getCondition();
		$query->andPredicates(
			$query->eq('productList', $productListItem->getProductList()),
			$query->eq('condition', $condition),
			$query->lt('position', $query->getFragmentBuilder()->number($productListItem->getPosition()))
		);

		$query->addOrder('position', false);
		/* @var $upCat \Rbs\Catalog\Documents\ProductListItem */
		$upCat = $query->getFirstDocument();
		if ($upCat)
		{
			$tm = $this->getTransactionManager();
			try
			{
				$tm->begin();
				$toPosition = $upCat->getPosition();
				$fromPosition = $productListItem->getPosition();
				$productListItem->setPosition($toPosition);
				$upCat->setPosition($fromPosition);
				$productListItem->save();
				$upCat->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @throws \RuntimeException
	 */
	public function highlightProductListItemTop($productListItem)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
		$condition = $productListItem->getCondition();
		$query->andPredicates(
			$query->eq('productList', $productListItem->getProductList()),
			$query->eq('condition', $condition),
			$query->lt('position', $query->getFragmentBuilder()->number(0))
		);
		$query->addOrder('position', true);
		$topProductListItem = $query->getFirstDocument();
		if ($topProductListItem == null)
		{
			return;
		}
		/* @var $topProductListItem \Rbs\Catalog\Documents\ProductListItem */
		if ($topProductListItem->getId() == $productListItem->getId())
		{
			return;
		}
		$this->highlightProductListItem($productListItem, $topProductListItem);
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @throws \RuntimeException
	 */
	public function highlightProductListItemBottom($productListItem)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		$this->highlightProductListItem($productListItem);
	}

	/**
	 * @param  \Rbs\Catalog\Documents\ProductListItem|integer $productListItem
	 * @throws \RuntimeException
	 */
	public function deleteProductListItem($productListItem)
	{
		if (is_numeric($productListItem))
		{
			$productListItem = $this->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		if ($productListItem->isHighlighted())
		{
			$this->downplayProductListItem($productListItem);
		}
		$productListItem->delete();
	}

	/**
	 * @param \Rbs\Website\Documents\Section $section
	 * @return \Change\Documents\AbstractDocument|null
	 */
	public function getDefaultProductListBySection($section)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_SectionProductList');
		$query->andPredicates(
			$query->eq('synchronizedSection', $section)
		);
		$query->addOrder('id', false);
		$defaultProductList = $query->getFirstDocument();

		return $defaultProductList;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $webStoreId
	 * @param \Rbs\Price\Documents\BillingArea $billingArea
	 * @return null|\Rbs\Price\PriceInterface
	 */
	public function getProductPrice($product, $webStoreId, $billingArea)
	{
		if ($product->hasVariants())
		{
			$prices = array();

			$skus = $product->getAllSkuOfVariant(true);
			foreach ($skus as $sku)
			{
				$p = $this->priceManager->getPriceBySku($sku, ['webStore' => $webStoreId, 'billingArea' => $billingArea]);
				if ($p != null)
				{
					$prices[] = $p;
				}
			}

			$lowestPrice = null;
			foreach ($prices as $price)
			{
				if ($lowestPrice == null)
				{
					$lowestPrice = $price;
				}
				else
				{
					if ($price->getValue() < $lowestPrice->getValue())
					{
						$lowestPrice = $price;
					}
				}
			}

			return $lowestPrice;
		}
		else
		{
			return $this->priceManager->getPriceBySku($product->getSku(),
				['webStore' => $webStoreId, 'billingArea' => $billingArea]);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer|null $webStoreId
	 * @return integer|null
	 */
	public function getProductStockLevel($product, $webStoreId = null)
	{
		$level = null;

		if ($product->hasVariants())
		{
			$skus = $product->getAllSkuOfVariant(true);
			if ($skus !== null && $skus->count() > 0)
			{
				$level = $this->getStockManager()->getInventoryLevelForManySku($skus, $webStoreId);
			}
		}
		else
		{
			$sku = $product->getSku();
			if ($sku)
			{
				$level = $this->getStockManager()->getInventoryLevel($sku, $webStoreId);
			}
		}

		return $level;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $level
	 * @param integer|null $webStoreId
	 * @return string
	 */
	public function getProductThreshold($product, $level, $webStoreId = null)
	{
		$threshold = null;

		if ($product->hasVariants())
		{
			$skus = $product->getAllSkuOfVariant(true);
			if ($skus !== null && $skus->count() > 0)
			{
				$threshold = $this->getStockManager()->getInventoryThresholdForManySku($skus, $webStoreId, $level);
			}
		}
		else
		{
			$sku = $product->getSku();
			if ($sku)
			{
				$threshold = $this->getStockManager()->getInventoryThreshold($sku, $webStoreId, $level);
			}
		}

		return $threshold;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param null|integer $webStoreId
	 * @return array
	 */
	public function getStockInfo($product, $webStoreId = null)
	{
		$stockInfo = array();

		$level = $this->getProductStockLevel($product, $webStoreId);
		$threshold = $this->getProductThreshold($product, $level, $webStoreId);

		$stockInfo['level'] = $level;
		$stockInfo['threshold'] = $threshold;
		$stockInfo['thresholdClass'] = 'stock-' . \Change\Stdlib\String::toLower($threshold);
		switch ($threshold)
		{
			case \Rbs\Stock\StockManager::THRESHOLD_AVAILABLE:
				$stockInfo['thresholdClass'] .= ' alert-success';
				break;
			case \Rbs\Stock\StockManager::THRESHOLD_UNAVAILABLE:
				$stockInfo['thresholdClass'] .= 'alert-danger';
				break;
		}

		$stockInfo['thresholdTitle'] = $this->getStockManager()->getInventoryThresholdTitle($threshold);

		if ($product->hasVariants())
		{
			$tmpSkuCode = preg_replace('/[^a-zA-Z0-9]+/', '-',
				String::stripAccents(String::toUpper($product->getLabel())) . time());
			$stockInfo['sku'] = String::subString($tmpSkuCode, 0, 80);
			$stockInfo['minQuantity'] = 1;
			$stockInfo['maxQuantity'] = $level;
			$stockInfo['quantityIncrement'] = 1;
		}
		else
		{
			$sku = $product->getSku();

			if ($sku)
			{
				$stockInfo['sku'] = $sku->getCode();
				$stockInfo['minQuantity'] = $sku->getMinQuantity();
				$stockInfo['maxQuantity'] = $sku->getMaxQuantity() ? min(max($sku->getMinQuantity(), $sku->getMaxQuantity()),
					$level) : $level;
				$stockInfo['quantityIncrement'] = $sku->getQuantityIncrement() ? $sku->getQuantityIncrement() : 1;
			}
		}

		return $stockInfo;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $quantity
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @param string $zone
	 * @param integer|null $webstoreId
	 * @return array
	 */
	public function getPricesInfos($product, $quantity, $billingArea, $zone, $webstoreId = null)
	{
		$priceInfo = array();

		if ($billingArea)
		{
			$price = $this->getProductPrice($product, $webstoreId, $billingArea);

			if ($price && ($value = $price->getValue()) !== null)
			{
				$priceManager = $this->getPriceManager();

				$value *= $quantity;
				$isWithTax = $price->isWithTax();
				$taxCategories = $price->getTaxCategories();

				$priceInfo['currencyCode'] = $currencyCode = $billingArea->getCurrencyCode();
				if ($zone)
				{
					if ($isWithTax)
					{
						$taxes = $priceManager->getTaxByValueWithTax($value, $taxCategories, $billingArea, $zone);
					}
					else
					{
						$taxes = $priceManager->getTaxByValue($value, $taxCategories, $billingArea, $zone);
					}
				}
				else
				{
					$taxes = null;
				}

				if ($isWithTax)
				{
					$priceInfo['priceWithTax'] = $value;
					$priceInfo['formattedPriceWithTax'] = $priceManager->formatValue($value, $currencyCode);
					if ($taxes)
					{
						$value = $priceManager->getValueWithoutTax($value, $taxes);
						$priceInfo['price'] = $value;
						$priceInfo['formattedPrice'] = $priceManager->formatValue($value, $currencyCode);
					}
				}
				else
				{
					$priceInfo['price'] = $value;
					$priceInfo['formattedPrice'] = $priceManager->formatValue($value, $currencyCode);
					if ($taxes)
					{
						$value = $priceManager->getValueWithTax($value, $taxes);
						$priceInfo['priceWithTax'] = $value;
						$priceInfo['formattedPriceWithTax'] = $priceManager->formatValue($value, $currencyCode);
					}
				}

				if (($oldValue = $price->getBasePriceValue()) !== null)
				{
					$oldValue *= $quantity;
					if ($zone)
					{
						if ($isWithTax)
						{
							$taxes = $priceManager->getTaxByValueWithTax($oldValue, $taxCategories, $billingArea, $zone);
						}
						else
						{
							$taxes = $priceManager->getTaxByValue($oldValue, $taxCategories, $billingArea, $zone);
						}
					}
					else
					{
						$taxes = null;
					}
					if ($isWithTax)
					{
						$priceInfo['priceWithoutDiscountWithTax'] = $oldValue;
						$priceInfo['formattedPriceWithoutDiscountWithTax'] = $priceManager->formatValue($oldValue,
							$currencyCode);
						if ($taxes)
						{
							$oldValue = $priceManager->getValueWithoutTax($oldValue, $taxes);
							$priceInfo['priceWithoutDiscount'] = $oldValue;
							$priceInfo['formattedPriceWithoutDiscount'] = $priceManager->formatValue($oldValue,
								$currencyCode);
						}
					}
					else
					{
						$priceInfo['priceWithoutDiscount'] = $oldValue;
						$priceInfo['formattedPriceWithoutDiscount'] = $priceManager->formatValue($oldValue, $currencyCode);
						if ($taxes)
						{

							$oldValue = $priceManager->getValueWithTax($oldValue, $taxes);
							$priceInfo['priceWithoutDiscountWithTax'] = $oldValue;
							$priceInfo['formattedPriceWithoutDiscountWithTax'] = $priceManager->formatValue($oldValue,
								$currencyCode);
						}
					}
				}

				if ($price instanceof \Rbs\Price\Documents\Price)
				{
					if ($price->getEcoTax() !== null)
					{
						$priceInfo['ecoTax'] = ($price->getEcoTax() * $quantity);
						$priceInfo['formattedEcoTax'] = $priceManager->formatValue($priceInfo['ecoTax'], $currencyCode);
					}
				}
			}
		}

		return $priceInfo;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Change\Http\Web\UrlManager|null $urlManager
	 * @return array
	 */
	public function getGeneralInfo($product, $urlManager = null)
	{
		$generalInfo = array();

		$generalInfo['id'] = $product->getId();
		$generalInfo['product'] = $product;
		$generalInfo['title'] = $product->getCurrentLocalization()->getTitle();
		$generalInfo['description'] = $product->getCurrentLocalization()->getDescription();
		$generalInfo['hasVariants'] = $product->hasVariants();
		$generalInfo['hasOwnSku'] = $product->getSku() !== null ? true : false;

		if ($product->getBrand() && $product->getBrand()->published())
		{
			$generalInfo['brand'] = $product->getBrand();
		}

		if ($urlManager instanceof \Change\Http\Web\UrlManager)
		{
			$generalInfo['url'] = $urlManager->getCanonicalByDocument($product)->normalize()->toString();
		}

		return $generalInfo;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return array
	 */
	public function getVariantsConfiguration($product)
	{
		$variantsConfiguration = array();

		// TODO use hasVariant ?
		if ($product->getVariantGroup())
		{
			$variantsConfiguration['variantGroup'] = $product->getVariantGroup();
			$variantsConfiguration['axes'] = $this->getAttributeManager()
				->buildVariantConfiguration($product->getVariantGroup(), true);
			$variantsConfiguration['axesNames'] = $this->getAxesNames($product->getVariantGroup(), $this->getDocumentManager());
		}

		return $variantsConfiguration;
	}

	/**
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return array
	 */
	protected function getAxesNames($variantGroup, $documentManager)
	{
		$axesNames = array();
		$configuration = $variantGroup->getAxesConfiguration();
		if (is_array($configuration) && count($configuration))
		{
			foreach ($configuration as $confArray)
			{
				$conf = (new \Rbs\Catalog\Product\AxisConfiguration())->fromArray($confArray);
				/* @var $axeAttribute \Rbs\Catalog\Documents\Attribute */
				$axeAttribute = $documentManager->getDocumentInstance($conf->getId());
				if ($axeAttribute)
				{
					$axesNames[] = $axeAttribute->getCurrentLocalization()->getTitle();
				}
			}
		}
		return $axesNames;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return array
	 */
	public function getAttributesConfiguration($product)
	{
		$attributesConfiguration = array();

		$attributesConfiguration['attributesConfig'] = $this->getAttributeManager()
			->getProductAttributesConfiguration('specifications', $product);

		return $attributesConfiguration;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $formats
	 * @return array
	 */
	public function getVisualsInfos($product,
		$formats = array('list' => ['maxWidth' => 160, 'maxHeight' => 120], 'detail' => ['maxWidth' => 540, 'maxHeight' => 405],
			'thumbnail' => ['maxWidth' => 80, 'maxHeight' => 60], 'attribute' => ['maxWidth' => 160, 'maxHeight' => 120]))
	{
		$visualsInfos = array();
		$visualsInfos['visualsInstance'] = array();
		$visualsInfos['visuals'] = array();

		if ($product->getVisualsCount() > 0)
		{
			$visualsInfos['visualsInstance'] = $product->getVisuals();
			$visualsInfos['count'] = $product->getVisualsCount();
		}
		else
		{
			if ($product->getVariantGroup())
			{
				if ($product->getVariant())
				{
					// Get visual of first upper product
					// TODO
				}
				else
				{
					// Get visual of one of under product
					// TODO
				}
			}
			$visualsInfos['count'] = count($visualsInfos['visualsInstance']);
		}

		foreach ($visualsInfos['visualsInstance'] as $instance)
		{
			$v = array('id' => $instance->getId(), 'alt' => $instance->getCurrentLocalization()->getAlt(), 'url' => array());
			$v['url']['main'] = $instance->getPublicURL();

			// Foreach formats, generate URL
			foreach($formats as $type => $values)
			{
				$w = null;
				$h = null;

				if (isset($values['maxWidth']))
				{
					$w = $values['maxWidth'];
				}
				if (isset($values['maxHeight']))
				{
					$h = $values['maxHeight'];
				}

				$v['url'][$type] = $instance->getPublicURL(intval($w), intval($h));

			}

			$visualsInfos['visuals'][] = $v;
		}

		return $visualsInfos;
	}
}