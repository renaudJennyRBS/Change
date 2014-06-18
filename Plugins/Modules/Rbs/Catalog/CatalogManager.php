<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/CatalogManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_GET_PICTOGRAMS, [$this, 'onDefaultGetPictograms'], 5);
		$eventManager->attach(static::EVENT_GET_VISUALS, [$this, 'onDefaultGetVisuals'], 5);
		$eventManager->attach('getProductPresentation', [$this, 'onVariantGetProductPresentation'], 15);
		$eventManager->attach('getProductPresentation', [$this, 'onSetGetProductPresentation'], 10);
		$eventManager->attach('getProductPresentation', [$this, 'onDefaultGetProductPresentation'], 5);

		$itemOrdering = null;

		$eventManager->attach('updateItemOrdering', function($event) use (&$itemOrdering) {
			if ($itemOrdering === null)
			{
				$itemOrdering = new \Rbs\Catalog\Events\ItemOrdering();
			}
			$itemOrdering->onUpdateProductListItem($event);
		}, 5);
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

	//
	// Product lists.
	//

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

	//
	// Variants.
	//

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return \Rbs\Catalog\Documents\Product
	 */
	public function getProductToBeDisplayed($product)
	{
		// If product is a simple product or is root product of variant or is categorizable.
		if (!$product->getVariantGroup() || $product->hasVariants() ||  $product->getCategorizable())
		{
			return $product;
		}

		// Else you have a product that is a final product of variant.
		// If you have generated intermediate variant.
		if (!$product->getVariantGroup()->mustGenerateOnlyLastVariant())
		{
			// Try to find the intermediate variant that must be used to display product.
			$newProductId = $this->getVariantProductIdMustBeDisplayedForVariant($product);
			if ($newProductId != null)
			{
				$product = $this->getDocumentManager()->getDocumentInstance($newProductId);
				if ($product instanceof \Rbs\Catalog\Documents\Product)
				{
					return $product;
				}
			}
		}

		// Else try to return the root product of variant.
		return $this->getRootProductOfVariantGroup($product->getVariantGroup());
	}

	/**
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @return \Rbs\Catalog\Documents\Product|null
	 */
	protected function getRootProductOfVariantGroup($variantGroup)
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
	protected function getVariantProductIdMustBeDisplayedForVariant($variant)
	{
		$ancestorId = $this->getProductAncestorIds($variant, true);

		if ($ancestorId && count($ancestorId) > 0)
		{
			return $ancestorId[0];
		}

		return null;
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @return array
	 */
	public function getVariantInfo($product)
	{
		$variantInfo = array();

		if ($product->getVariantGroup())
		{
			$vConfiguration = $this->getVariantsConfiguration($product, false);

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

	//
	// Product presentation.
	//

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param \Change\Http\Web\UrlManager|null $urlManager
	 * @return array
	 */
	public function getGeneralInfo($product, $urlManager = null)
	{
		$generalInfo = array();

		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return $generalInfo;
		}

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
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @return null|\Rbs\Price\PriceInterface
	 */
	protected function getProductPrice($product, $webStore, $billingArea)
	{
		if ($product->getSku())
		{
			return $this->priceManager->getPriceBySku($product->getSku(),
				['webStore' => $webStore, 'billingArea' => $billingArea]);
		}
		else
		{
			$skus = $this->getAllSku($product, true);
			if (!count($skus))
			{
				return null;
			}

			$prices = array();
			foreach ($skus as $sku)
			{
				$price = $this->priceManager->getPriceBySku($sku, ['webStore' => $webStore, 'billingArea' => $billingArea]);
				if ($price != null)
				{
					$prices[] = $price;
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
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Store\Documents\WebStore|null $webStore
	 * @return integer|null
	 */
	protected function getProductStockLevel($product, $webStore = null)
	{
		$level = null;
		if ($product->getSku())
		{
			$sku = $product->getSku();
			if ($sku)
			{
				$level = $this->getStockManager()->getInventoryLevel($sku, $webStore);
			}
		}
		else
		{
			$skus = $this->getAllSku($product, true);
			if (count($skus))
			{
				$level = $this->getStockManager()->getInventoryLevelForManySku($skus, $webStore);
			}
		}
		return $level;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param integer $level
	 * @param \Rbs\Store\Documents\WebStore|null $webStore
	 * @return string
	 */
	protected function getProductThreshold($product, $level, $webStore = null)
	{
		$threshold = null;
		if ($product->getSku())
		{
			$sku = $product->getSku();
			if ($sku)
			{
				$threshold = $this->getStockManager()->getInventoryThreshold($sku, $webStore, $level);
			}
		}
		else
		{
			$skus = $this->getAllSku($product, true);
			if (count($skus))
			{
				$threshold = $this->getStockManager()->getInventoryThresholdForManySku($skus, $webStore, $level);
			}
		}
		return $threshold;
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param \Rbs\Store\Documents\WebStore|null $webStore
	 * @return array
	 */
	public function getStockInfo($product, $webStore = null)
	{
		$stockInfo = array();

		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return $stockInfo;
		}

		$level = $this->getProductStockLevel($product, $webStore);
		$threshold = $this->getProductThreshold($product, $level, $webStore);

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
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param integer $quantity
	 * @param \Rbs\Store\Documents\WebStore $webStore
	 * @param \Rbs\Price\Tax\BillingAreaInterface $billingArea
	 * @param string $zone
	 * @return array
	 */
	public function getPricesInfos($product, $quantity, $webStore, $billingArea, $zone)
	{
		$priceInfo = array();

		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		if (!($product instanceof \Rbs\Catalog\Documents\Product) || !$billingArea)
		{
			return $priceInfo;
		}

		$price = $this->getProductPrice($product, $webStore, $billingArea);
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
				if (is_array($taxes))
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
					if (is_array($taxes))
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
					if (is_array($taxes))
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
				if ($price->getDiscountDetail())
				{
					$priceInfo['discountDetail'] = $price->getDiscountDetail();
				}
			}
		}

		return $priceInfo;
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param boolean $onlyPublishedProduct
	 * @return array
	 */
	public function getVariantsConfiguration($product, $onlyPublishedProduct = true)
	{
		$variantsConfiguration = array();
		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return $variantsConfiguration;
		}

		// TODO Active a cache on variant group
		if ($product->getVariantGroup())
		{
			$variantsConfiguration['variantGroup'] = $product->getVariantGroup();
			$variantsConfiguration['axes'] = $this->getAttributeManager()
				->buildVariantConfiguration($product->getVariantGroup(), $onlyPublishedProduct);
			$variantsConfiguration['axesNames'] = $this->getAxesNames($product->getVariantGroup(), $this->getDocumentManager());
		}

		return $variantsConfiguration;
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param boolean $onlyPublishedProduct
	 * @return array
	 */
	public function getAllSku($product, $onlyPublishedProduct = false)
	{
		if ($product->getProductSet())
		{
			$set = $product->getProductSet();
			$skus = array();
			foreach($set->getProducts() as $subProduct)
			{
				if ($onlyPublishedProduct && !$subProduct->published())
				{
					continue;
				}
				elseif ($subProduct->getSku())
				{
					$skus[] = $subProduct->getSku();
				}
				else
				{
					$skus = array_merge($skus, $this->getAllSku($subProduct));
				}
			}
			return $skus;
		}
		else
		{
			if (!$product->getSku() && $product->getVariantGroup())
			{
				// If root product.
				if ($product->hasVariants())
				{
					$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_Sku');
					$productQuery = $query->getPropertyModelBuilder('id', 'Rbs_Catalog_Product', 'sku');
					$productQuery->andPredicates($productQuery->eq('variant', true),
						$productQuery->eq('variantGroup', $product->getVariantGroup()));
					if ($onlyPublishedProduct)
					{
						$productQuery->andPredicates($productQuery->published());
					}
					return $query->getDocuments()->toArray();
				}
				else
				{
					$skus = array();
					$products = $this->getProductDescendants($product, true);
					foreach ($products as $subProduct)
					{
						$skus[] = $subProduct->getSku();
					}
					return $skus;
				}
			}
		}
		return array();
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param boolean $onlyPublishedProduct
	 * @return \Rbs\Catalog\Documents\Product[]
	 */
	public function getProductsBySku($sku, $onlyPublishedProduct)
	{
		$products = [];

		if ($sku instanceof \Rbs\Stock\Documents\Sku)
		{
			$q = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
			$productArray = $q->andPredicates($q->eq('sku', $sku))->getDocuments();

			/** @var $product \Rbs\Catalog\Documents\Product */
			foreach ($productArray as $product)
			{
				if (isset($products[$product->getId()]))
				{
					continue;
				}
				$products[$product->getId()] = $product;
				foreach ($this->getProductAncestors($product, false) as $ancestorProduct)
				{
					if (isset($products[$ancestorProduct->getId()]))
					{
						continue;
					}
					$products[$ancestorProduct->getId()] = $ancestorProduct;
				}
			}
		}
		return array_values($products);
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param boolean $onlyPublishedProduct
	 * @return integer[]
	 */
	protected function getProductAncestorIds($product, $onlyPublishedProduct)
	{
		if (!$product->getVariant())
		{
			return array();
		}

		// Look for the axes configuration of the product.
		$productAxesConfiguration = null;
		$variantConfiguration = $this->getAttributeManager()
			->buildVariantConfiguration($product->getVariantGroup(), $onlyPublishedProduct);
		foreach ($variantConfiguration['products'] as $infoProduct)
		{
			if ($infoProduct['id'] === $product->getId())
			{
				$productAxesConfiguration = $infoProduct;
				break;
			}
		}

		// Look for the product with the same values for the first axes and null for the other ones.
		$ancestors = array();
		foreach ($variantConfiguration['products'] as $infoProduct)
		{
			$keyParts = array();
			foreach ($infoProduct['values'] as $level => $axis)
			{
				if ($axis['value'] === null)
				{
					$ancestors[implode('/', $keyParts)] = $infoProduct['id'];
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
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param boolean $onlyPublishedProduct
	 * @return \Rbs\Catalog\Documents\Product[]
	 */
	protected function getProductAncestors($product, $onlyPublishedProduct)
	{
		$ancestorsId = $this->getProductAncestorIds($product, $onlyPublishedProduct);
		$ancestors = array();

		$dm = $this->getDocumentManager();
		foreach ($ancestorsId as $key => $value)
		{
			$ancestors[$key] = $dm->getDocumentInstance($value, 'Rbs_catalog_Product');
		}

		return $ancestors;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param boolean $onlyPublishedProduct
	 * @return integer[]
	 */
	public function getProductDescendantIds($product, $onlyPublishedProduct)
	{
		// Is Root product
		if ($product->hasVariants())
		{
			// Get all published variant
			$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
			$query->andPredicates($query->eq('variant', true), $query->eq('variantGroup', $product->getVariantGroup()),
				$query->neq('id', $product->getId()));
			if ($onlyPublishedProduct)
			{
				$query->andPredicates($query->published());
			}

			return $query->getDocuments()->ids();
		}

		// Is not Root product
		// Look for the axes configuration of the product.
		$productAxesConfiguration = null;
		$variantConfiguration = $this->getAttributeManager()
			->buildVariantConfiguration($product->getVariantGroup(), $onlyPublishedProduct);

		foreach ($variantConfiguration['products'] as $infoProduct)
		{
			if ($infoProduct['id'] === $product->getId())
			{
				$productAxesConfiguration = $infoProduct;
				break;
			}
		}

		// Look for the product with the same values for the first axes and null for the other ones.
		$descendants = array();
		foreach ($variantConfiguration['products'] as $infoProduct)
		{
			if ($infoProduct['id'] !== $productAxesConfiguration['id'])
			{
				$add = true;
				foreach ($productAxesConfiguration['values'] as $index => $confValues)
				{
					if ($confValues['value'] !== null && $infoProduct['values'][$index]['value'] != $confValues['value'])
					{
						$add = false;
						break;
					}
				}
				if ($add)
				{
					$descendants[] = $infoProduct['id'];
				}
			}
		}

		return array_values($descendants);
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param boolean $onlyPublishedProduct
	 * @return \Rbs\Catalog\Documents\Product[]
	 */
	public function getProductDescendants($product, $onlyPublishedProduct)
	{
		$descendantsId = $this->getProductDescendantIds($product, $onlyPublishedProduct);
		$descendants = array();

		$dm = $this->getDocumentManager();
		foreach ($descendantsId as $key => $value)
		{
			$descendants[$key] = $dm->getDocumentInstance($value, 'Rbs_catalog_Product');
		}

		return $descendants;
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
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param string $visibility
	 * @return array
	 */
	public function getAttributesConfiguration($product, $visibility = 'specifications')
	{
		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		return $this->getAttributeManager()->getProductAttributesConfiguration($visibility, $product);
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param array $formats
	 * @param boolean $onlyFirst
	 * @return array
	 */
	public function getVisualsInfos($product, $formats, $onlyFirst = false)
	{
		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return array();
		}

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
			$visualsInfos['instances'] = $onlyFirst ? [$product->getFirstVisual()] : $product->getVisuals();
		}
		elseif ($product->getVariantGroup())
		{
			if ($product->getVariant())
			{
				$ancestors = $this->getProductAncestors($product, true);
				$ancestors = array_reverse($ancestors);
				$ancestors[] = $product->getVariantGroup()->getRootProduct();
				foreach ($ancestors as $ancestor)
				{
					/* @var $ancestor \Rbs\Catalog\Documents\Product */
					if ($ancestor->getVisualsCount() > 0)
					{
						$visualsInfos['instances'] = $onlyFirst ? [$ancestor->getFirstVisual()] : $ancestor->getVisuals();
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
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param array $formats
	 * @return array
	 */
	public function getPictogramsInfos($product, $formats)
	{
		if (is_numeric($product))
		{
			$product = $this->getDocumentManager()->getDocumentInstance($product);
		}
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return array();
		}

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

	/**
	 * Used for product sets.
	 * @api
	 * @param \Rbs\Catalog\Product\ProductPresentation $productPresentation
	 * @param array $options
	 * @return \Rbs\Catalog\Product\ProductPresentation[]
	 */
	public function getSubProductPresentations($productPresentation, $options)
	{
		$product = $this->getDocumentManager()->getDocumentInstance($productPresentation->getProductId());
		if (!($product instanceof \Rbs\Catalog\Documents\Product))
		{
			return array();
		}

		$set = $product->getProductSet();
		if (!($set instanceof \Rbs\Catalog\Documents\ProductSet))
		{
			return array();
		}

		$subProductsPresentations = array();
		foreach($set->getProducts() as $subProduct)
		{
			$subProductsPresentations[] = $this->getProductPresentation($subProduct, $options);
		}
		return $subProductsPresentations;
	}

	/**
	 * Default options:
	 *  - urlManager
	 *  - webStore (got from context if not specified)
	 *  - billingArea (got from context if not specified)
	 *  - zone (got from context if not specified)
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param array $options
	 * @return \Rbs\Catalog\Product\ProductPresentation
	 */
	public function getProductPresentation($product, $options)
	{
		$em = $this->getEventManager();
		if (!is_array($options))
		{
			$options = array();
		}
		$options['product'] = $product;
		$args = $em->prepareArgs($options);
		$this->getEventManager()->trigger('getProductPresentation', $this, $args);
		if (isset($args['productPresentation']))
		{
			return $args['productPresentation'];
		}
		return array();
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetProductPresentation(\Change\Events\Event $event)
	{
		$productPresentation = $event->getParam('productPresentation');
		if (!($productPresentation instanceof \Rbs\Catalog\Product\ProductPresentation))
		{
			$productPresentation = new \Rbs\Catalog\Product\ProductPresentation();
			$event->setParam('productPresentation', $productPresentation);
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$productPresentation->setCatalogManager($this);
		$productPresentation->setUrlManager($event->getParam('urlManager'));

		$webStore = $event->getParam('webStore');
		if (!$webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$webStore = $commerceServices->getContext()->getWebStore();
		}
		$productPresentation->setWebStore($webStore);

		$billingArea = $event->getParam('billingArea');
		if (!$billingArea instanceof \Rbs\Price\Tax\BillingAreaInterface)
		{
			$billingArea = $commerceServices->getContext()->getBillingArea();
		}
		$productPresentation->setBillingArea($billingArea);

		$zone = $event->getParam('zone');
		if (!$zone)
		{
			$zone = $commerceServices->getContext()->getZone();
		}
		$productPresentation->setZone($zone);

		$product = $event->getParam('product');
		if ($product instanceof \Rbs\Catalog\Documents\Product)
		{
			$productId = $product->getId();
		}
		else
		{
			$productId = intval($product);
		}
		$productPresentation->setProductId($productId);

		$general = $event->getParam('general');
		if (is_array($general))
		{
			$productPresentation->setGeneral($general);
		}
		elseif ($productId)
		{
			$productPresentation->addGeneral($this->getGeneralInfo($productId, $event->getParam('urlManager')));
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onVariantGetProductPresentation(\Change\Events\Event $event)
	{
		$productPresentation = $event->getParam('productPresentation');
		if (!($productPresentation instanceof \Rbs\Catalog\Product\ProductPresentation))
		{
			$product = $event->getParam('product');
			if (is_numeric($product))
			{
				$product = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($product);
			}
			if ($product instanceof \Rbs\Catalog\Documents\Product && $product->getVariantGroupId())
			{
				$productPresentation = new \Rbs\Catalog\Product\ProductVariantPresentation();
				$event->setParam('productPresentation', $productPresentation);
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onSetGetProductPresentation(\Change\Events\Event $event)
	{
		$productPresentation = $event->getParam('productPresentation');
		if (!($productPresentation instanceof \Rbs\Catalog\Product\ProductPresentation))
		{
			$product = $event->getParam('product');
			if (is_numeric($product))
			{
				$product = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($product);
			}
			if ($product instanceof \Rbs\Catalog\Documents\Product && $product->getProductSetId())
			{
				$productPresentation = new \Rbs\Catalog\Product\ProductSetPresentation();
				$event->setParam('productPresentation', $productPresentation);
			}
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductListItem $productListItem
	 * @param array $options
	 * @throws \Exception
	 */
	public function updateItemOrdering($productListItem, array $options = null)
	{
		if (!is_array($options))
		{
			$options = [];
		}
		$options['productListItem'] = $productListItem;
		$this->getEventManager()->trigger('updateItemOrdering', $this, $options);
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param array $options
	 * @throws \Exception
	 */
	public function updateItemsOrdering($product, array $options = null)
	{
		if (!is_array($options))
		{
			$options = [];
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Catalog_ProductListItem');
		$items = $query->andPredicates($query->eq('product', $product))->getDocuments();
		if ($items->count())
		{
			try
			{
				$this->getTransactionManager()->begin();
				foreach ($items as $item)
				{
					$this->updateItemOrdering($item, $options);
				}
				$this->getTransactionManager()->commit();
			}
			catch (\Exception $e)
			{
				throw $this->getTransactionManager()->rollBack($e);
			}
		}
	}
}