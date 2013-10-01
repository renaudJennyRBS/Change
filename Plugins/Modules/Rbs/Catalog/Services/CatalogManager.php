<?php
namespace Rbs\Catalog\Services;

/**
 * @name \Rbs\Catalog\Services\CatalogManager
 */
class CatalogManager
{
	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 */
	public function setCommerceServices(\Rbs\Commerce\Services\CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
	}

	/**
	 * @return \Rbs\Commerce\Services\CommerceServices
	 */
	public function getCommerceServices()
	{
		return $this->commerceServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	protected function getDocumentServices()
	{
		return $this->commerceServices->getDocumentServices();
	}

	/**
	 * @return \Change\Application\ApplicationServices
	 */
	protected function getApplicationServices()
	{
		return $this->commerceServices->getApplicationServices();
	}

	/**
	 * Add the product in a product list for the given condition/priority.
	 *
	 * @api
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\Documents\ProductList $productList
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @return \Rbs\Catalog\Documents\ProductListItem
	 * @throws \Exception
	 */
	public function addProductInProductList(\Rbs\Catalog\Documents\Product $product, \Rbs\Catalog\Documents\ProductList $productList, $condition)
	{
		$ds = $this->getCommerceServices()->getDocumentServices();
		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
		$productListItem = null;
		try
		{
			$tm->begin();
			$productListItem = $this->getProductListItem($product, $productList, $condition);
			if (!$productListItem)
			{
				$productListItem = $ds->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductListItem');
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
	public function removeProductFromProductList(\Rbs\Catalog\Documents\Product $product, \Rbs\Catalog\Documents\ProductList $productList, $condition)
	{
		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
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
	public function getProductListItem(\Rbs\Catalog\Documents\Product $product, \Rbs\Catalog\Documents\ProductList $productList, $condition)
	{
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductListItem');
		$query->andPredicates($query->eq('product', $product), $query->eq('productList', $productList), $query->eq('condition', $condition));
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
	public function highlightProductInProductList(\Rbs\Catalog\Documents\Product $product, \Rbs\Catalog\Documents\ProductList $productList, $condition = null, $before = null)
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
	public function downplayProductInProductList(\Rbs\Catalog\Documents\Product $product, \Rbs\Catalog\Documents\ProductList $productList, $condition = null)
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
			$productListItem = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		$productList = $productListItem->getProductList();
		$condition = $productListItem->getCondition();
		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
		$updateQuery = $this->getCommerceServices()->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
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
			$productListItem = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
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
		$updateQuery = $this->getCommerceServices()->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
		$fb = $updateQuery->getFragmentBuilder();
		$positionColumn = $fb->getDocumentColumn('position');
		$conditionId = ($condition) ? $condition->getId() : 0;
		$where = $fb->logicAnd(
			$fb->lt($fb->getDocumentColumn('position'), $fb->number(0)),
			$fb->eq($fb->getDocumentColumn('productList'), $fb->number($productList->getId())),
			$fb->eq($fb->getDocumentColumn('condition'), $fb->number($conditionId))
		);

		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
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
			$productListItem = $this->getCommerceServices()
						->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
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
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductListItem');
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
			$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
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
			$productListItem = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
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
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductListItem');
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
			$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
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
			$productListItem = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
		}
		if (!($productListItem instanceof \Rbs\Catalog\Documents\ProductListItem))
		{
			throw new \RuntimeException("Invalid Product List Item Identifier", 999999);
		}
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductListItem');
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
			$productListItem = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
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
			$productListItem = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productListItem);
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
}