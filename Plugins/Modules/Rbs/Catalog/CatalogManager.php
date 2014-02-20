<?php
namespace Rbs\Catalog;

use Change\Stdlib\String;

/**
 * @name \Rbs\Catalog\CatalogManager
 */
class CatalogManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'CatalogManager';

	const EVENT_GET_VISUALS = 'getVisuals';
	const EVENT_GET_PICTOGRAMS = 'getPictograms';

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CatalogManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_GET_PICTOGRAMS, [$this, 'onDefaultGetPictograms'], 5);
		$eventManager->attach(static::EVENT_GET_VISUALS, [$this, 'onDefaultGetVisuals'], 5);
	}

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
			// Nothing to do.
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
				// We are re-ordering.
				$atPosition = $currentPosition < $beforePosition ? $beforePosition - 1 : $beforePosition;
			}
			else
			{
				// We introduce a new node.
				$atPosition = $beforePosition - 1;
			}
		}
		if ($currentPosition === $atPosition)
		{
			// Nothing to do.
			return;
		}
		// Prepare what's needed for queries.
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
			// Nothing to do.
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
			// Nothing to do.
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
		if (!$product->getSku() && $product->getVariantGroup())
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
				/* @var $price \Rbs\Price\Documents\Price */
				/* @var $lowestPrice \Rbs\Price\Documents\Price */
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

		if (!$product->getSku() && $product->getVariantGroup())
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

		if (!$product->getSku() && $product->getVariantGroup())
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

		if (!$product->getSku() && $product->getVariantGroup())
		{
			$tmpSkuCode = preg_replace('/[^a-zA-Z0-9]+/', '-',
				String::stripAccents(String::toUpper($product->getLabel())) . time());
			$stockInfo['sku'] = String::subString($tmpSkuCode, 0, 80);
			$stockInfo['minQuantity'] = 1;
			$stockInfo['maxQuantity'] = \Rbs\Stock\StockManager::UNLIMITED_LEVEL;
			$stockInfo['quantityIncrement'] = 1;
		}
		else
		{
			$sku = $product->getSku();

			if ($sku)
			{
				$stockInfo['sku'] = $sku->getCode();
				$stockInfo['minQuantity'] = $sku->getMinQuantity();
				if ($sku->getAllowBackorders())
				{
					$stockInfo['maxQuantity'] = null;
				}
				else
				{
					$stockInfo['maxQuantity'] = $sku->getMaxQuantity() ? min(max($sku->getMinQuantity(), $sku->getMaxQuantity()),
						$level) : $level;
				}

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
	 * @param integer|null $webStoreId
	 * @return array
	 */
	public function getPricesInfos($product, $quantity, $billingArea, $zone, $webStoreId = null)
	{
		$priceInfo = array();
		if ($billingArea)
		{
			$price = $this->getProductPrice($product, $webStoreId, $billingArea);
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

		// Description must be present in product attributes and not empty
		$generalInfo['description'] = null;
		$pDescription = $product->getCurrentLocalization()->getDescription();
		if ($pDescription !== null && $pDescription->getRawText() !== null
			&& $this->getAttributeManager()->hasAttributeForProperty($product, 'description')
		)
		{
			$generalInfo['description'] = $pDescription;
		}

		$generalInfo['hasVariants'] = $product->hasVariants();

		if ($product->getSku() !== null)
		{
			$generalInfo['hasOwnSku'] = true;
			$generalInfo['allowBackorders'] = $product->getSku()->getAllowBackorders();
		}
		else
		{
			$generalInfo['hasOwnSku'] = false;
			$generalInfo['allowBackorders'] = false;
		}

		if ($product->getBrand() && $product->getBrand()->published()
			&& $this->getAttributeManager()->hasAttributeForProperty($product, 'brand')
		)
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

		// TODO Active a cache on variant group
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
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return array
	 */
	public function getVariantInfo($product)
	{
		$variantInfo = array();

		if ($product->getVariantGroup())
		{
			$vConfiguration = $this->getVariantsConfiguration($product);

			$variantInfo['isRoot'] = $product->hasVariants();
			$variantInfo['depth'] = count($vConfiguration['axes']['axesValues']);

			foreach ($vConfiguration['axes']['products'] as $infoProduct)
			{
				if ($infoProduct['id'] === $product->getId())
				{
					$variantInfo['isFinal'] = true;
					$variantInfo['level'] = $variantInfo['depth'];
					for ($i = 0; $i < count($infoProduct['values']); $i++)
					{
						if ($infoProduct['values'][$i]['value'] === null)
						{
							$variantInfo['isFinal'] = false;
							$variantInfo['level'] = $i;
							break;
						}
					}
				}
			}

			return $variantInfo;
		}

		return null;
	}

	/**
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @return \Rbs\Catalog\Documents\Product|null
	 */
	public function getRootProductOfVariantGroup($variantGroup)
	{
		$rootProduct = $variantGroup->getRootProduct();

		if ($rootProduct->published())
		{
			return $rootProduct;
		}
		return null;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $variant
	 * @return integer|null
	 */
	public function getVariantProductIdMustBeDisplayedForVariant($variant)
	{
		$axesConfiguration = $variant->getVariantGroup()->getAxesConfiguration();
		$axesCount = count($axesConfiguration);
		$axes = array();
		for ($i = 0; $i < $axesCount - 1; $i++)
		{
			if ($axesConfiguration[$i]['url'] === true)
			{
				$axesConfiguration[$i]['level'] = $i;
				$axes[] = $axesConfiguration[$i];
			}
		}
		$axes = array_reverse($axes);

		$newProductId = null;
		if (count($axes) > 0)
		{
			$productAxesConfiguration = null;
			$vConfiguration = $this->getVariantsConfiguration($variant);
			foreach ($vConfiguration['axes']['products'] as $infoProduct)
			{
				if ($infoProduct['id'] === $variant->getId())
				{
					$productAxesConfiguration = $infoProduct;
					break;
				}
			}

			foreach ($axes as $axis)
			{
				$productAxesConfigurationValue = $productAxesConfiguration['values'][$axis['level']]['value'];
				foreach ($vConfiguration['axes']['products'] as $infoProduct)
				{
					if ($infoProduct['values'][$axis['level']]['value'] == $productAxesConfigurationValue)
					{
						$newProductId = $infoProduct['id'];
						for ($i = ($axis['level'] + 1); $i < $axesCount; $i++)
						{
							if ($infoProduct['values'][$i]['value'] !== null)
							{
								$newProductId = null;
							}
						}
					}

					if ($newProductId != null)
					{
						break 2;
					}
				}
			}
		}

		return $newProductId;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return \Rbs\Catalog\Documents\Product[]
	 */
	public function getProductAncestors($product)
	{
		if (!$product->getVariant())
		{
			return array();
		}

		// Look for the axes configuration of the product.
		$productAxesConfiguration = null;
		$variantConfiguration = $this->getAttributeManager()->buildVariantConfiguration($product->getVariantGroup(), true);
		foreach ($variantConfiguration['products'] as $infoProduct)
		{
			if ($infoProduct['id'] === $product->getId())
			{
				$productAxesConfiguration = $infoProduct;
				break;
			}
		}

		// Look fot the product with the same values fort the first axes and null for the other ones.
		$ancestors = array();
		foreach ($variantConfiguration['products'] as $infoProduct)
		{
			$keyParts = array();
			foreach ($infoProduct['values'] as $level => $axis)
			{
				if ($axis['value'] === null)
				{
					$ancestors[implode('/', $keyParts)] = $this->getDocumentManager()->getDocumentInstance($infoProduct['id']);
					break;
				}
				elseif ($axis['value'] == $productAxesConfiguration['values'][$level]['value'])
				{
					$keyParts[] = $axis['value'];
				}
				else
				{
					break;
				}
			}
		}

		// Sort by key and return ancestors.
		ksort($ancestors);
		return array_values($ancestors);
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
	 * @param string $visibility
	 * @return array
	 */
	public function getAttributesConfiguration($product, $visibility = 'specifications')
	{
		return $this->getAttributeManager()->getProductAttributesConfiguration($visibility, $product);
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $formats
	 * @param boolean $onlyFirst
	 * @return array
	 */
	public function getVisualsInfos($product, $formats, $onlyFirst = false)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('product' => $product, 'formats' => $formats, 'onlyFirst' => $onlyFirst));
		$this->getEventManager()->trigger(static::EVENT_GET_VISUALS, $this, $args);
		if (isset($args['visualsInfos']))
		{
			return $args['visualsInfos'];
		}
		return array();
	}

	/**
	 * $event requires two parameters: product, formats and onlyFirst
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetVisuals(\Change\Events\Event $event)
	{
		$product = $event->getParam('product');
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return;
		}
		$formats = is_array($event->getParam('formats')) ? $event->getParam('formats') : array();
		$onlyFirst = $event->getParam('onlyFirst');

		$visualsInfos = array();
		$visualsInfos['instances'] = array();
		$visualsInfos['data'] = array();

		if ($product->getVisualsCount() > 0)
		{
			$visualsInfos['instances'] = $onlyFirst ? [ $product->getFirstVisual() ] : $product->getVisuals();
		}
		elseif ($product->getVariantGroup())
		{
			if ($product->getVariant())
			{
				$ancestors = $this->getProductAncestors($product);
				$ancestors = array_reverse($ancestors);
				$ancestors[] = $product->getVariantGroup()->getRootProduct();
				foreach ($ancestors as $ancestor)
				{
					/* @var $ancestor \Rbs\Catalog\Documents\Product */
					if ($ancestor->getVisualsCount() > 0)
					{
						$visualsInfos['instances'] = $onlyFirst ? [ $ancestor->getFirstVisual() ] : $ancestor->getVisuals();
						break;
					}
				}
			}

			// Get visual of one of under product.
			if (!count($visualsInfos['instances']))
			{
				$query = $this->getDocumentManager()->getNewQuery('Rbs_Media_Image');
				$pqb = $query->getModelBuilder('Rbs_Catalog_Product', 'visuals');
				$query->andPredicates(
					$pqb->getPredicateBuilder()->published(),
					$pqb->getPredicateBuilder()->eq('variantGroup', $product->getVariantGroup())
				);
				$visual = $query->getFirstDocument();
				if ($visual)
				{
					$visualsInfos['instances'][] = $visual;
				}
			}
		}
		$visualsInfos['count'] = count($visualsInfos['instances']);

		foreach ($visualsInfos['instances'] as $instance)
		{
			$visualsInfos['data'][] = $this->getImageInfos($instance, $formats);
		}

		$event->setParam('visualsInfos', $visualsInfos);
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $formats
	 * @return array
	 */
	public function getPictogramsInfos($product, $formats)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('product' => $product, 'formats' => $formats));
		$this->getEventManager()->trigger(static::EVENT_GET_PICTOGRAMS, $this, $args);
		if (isset($args['pictogramsInfos']))
		{
			return $args['pictogramsInfos'];
		}
		return array();
	}

	/**
	 * $event requires two parameters : product and formats
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetPictograms(\Change\Events\Event $event)
	{
		$product = $event->getParam('product');
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return;
		}
		$formats = is_array($event->getParam('formats')) ? $event->getParam('formats') : array();

		$pictogramsInfos = array();
		$pictogramsInfos['instances'] = array();
		$pictogramsInfos['data'] = array();

		// Variants always use the pictograms from the root product.
		if ($product->getVariantGroup())
		{
			$pictogramsInfos['instances'] = $product->getVariantGroup()->getRootProduct()->getPictograms();
		}
		else
		{
			$pictogramsInfos['instances'] = $product->getPictograms();
		}
		$pictogramsInfos['count'] = count($pictogramsInfos['instances']);

		foreach ($pictogramsInfos['instances'] as $instance)
		{
			$pictogramsInfos['data'][] = $this->getImageInfos($instance, $formats);
		}
		$event->setParam('pictogramsInfos', $pictogramsInfos);
	}

	/**
	 * @param \Rbs\Media\Documents\Image $image
	 * @param array $formats
	 * @return array
	 */
	protected function getImageInfos($image, $formats)
	{
		$infos = array('id' => $image->getId(), 'alt' => $image->getCurrentLocalization()->getAlt(), 'url' => array());
		$infos['url']['main'] = $image->getPublicURL();

		// Foreach formats, generate URL.
		foreach ($formats as $type => $values)
		{
			$w = isset($values['maxWidth']) ? $values['maxWidth'] : null;
			$h = isset($values['maxHeight']) ? $values['maxHeight'] : null;
			$infos['url'][$type] = $image->getPublicURL(intval($w), intval($h));
		}
		return $infos;
	}
}