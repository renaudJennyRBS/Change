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
	 * Add the product in a category for the given condition/priority.
	 *
	 * @api
	 * @param \Rbs\Catalog\Documents\AbstractProduct $product
	 * @param \Rbs\Catalog\Documents\Category $category
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @return \Rbs\Catalog\Documents\ProductCategorization
	 * @throws \Exception
	 */
	public function addProductInCategory(\Rbs\Catalog\Documents\AbstractProduct $product, \Rbs\Catalog\Documents\Category $category, $condition)
	{
		$ds = $this->getCommerceServices()->getDocumentServices();
		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
		$categorization = null;
		try
		{
			$tm->begin();
			$categorization = $this->getProductCategorization($product, $category, $condition);
			if (!$categorization)
			{
				$categorization = $ds->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_ProductCategorization');
				/* @var $categorization \Rbs\Catalog\Documents\ProductCategorization */
				$categorization->setProduct($product);
				$categorization->setCategory($category);
				$categorization->setCondition($condition);
			}
			$categorization->save();
			$tm->commit();

		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $categorization;
	}

	/**
	 * @param \Rbs\Catalog\Documents\AbstractProduct $product
	 * @param \Rbs\Catalog\Documents\Category $category
	 * @param $condition
	 */
	public function removeProductFromCategory(\Rbs\Catalog\Documents\AbstractProduct $product, \Rbs\Catalog\Documents\Category $category, $condition)
	{
		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$categorization = $this->getProductCategorization($product, $category, $condition);
			if ($categorization instanceof \Rbs\Catalog\Documents\ProductCategorization)
			{
				if ($categorization->isHighlighted())
				{
					$this->downplayProductInCategory($product, $category, $condition);
				}
				$categorization->delete();
			}
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\AbstractProduct $product
	 * @param \Rbs\Catalog\Documents\Category $category
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @return \Rbs\Catalog\Documents\ProductCategorization|null
	 */
	public function getProductCategorization(\Rbs\Catalog\Documents\AbstractProduct $product, \Rbs\Catalog\Documents\Category $category, $condition)
	{
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductCategorization');
		$query->andPredicates($query->eq('product', $product), $query->eq('category', $category), $query->eq('condition', $condition));
		return $query->getFirstDocument();
	}

	/**
	 * This method performs a bulk update, so you shouldn't be messing with positions at all!
	 *
	 * @api
	 * @param \Rbs\Catalog\Documents\AbstractProduct $product
	 * @param \Rbs\Catalog\Documents\Category $category
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 * @param \Rbs\Catalog\Documents\AbstractProduct $before
	 */
	public function highlightProductInCategory(\Rbs\Catalog\Documents\AbstractProduct $product, \Rbs\Catalog\Documents\Category $category, $condition = null, $before = null)
	{
		$productCategorization = $this->getProductCategorization($product, $category, $condition);
		if (!$productCategorization)
		{
			throw new \RuntimeException("Product to highlight is not in category", 999999);
		}
		$beforeProductCategorization = null;
		if ($before instanceof \Rbs\Catalog\Documents\AbstractProduct)
		{
			$beforeProductCategorization = $this->getProductCategorization($before, $category, $condition);
		}
		$this->highlightProductCategorization($productCategorization, $beforeProductCategorization);
	}

	/**
	 * This method performs a bulk update, so you shouldn't be messing with positions at all!
	 *
	 * @api
	 * @param \Rbs\Catalog\Documents\AbstractProduct $product
	 * @param \Rbs\Catalog\Documents\Category $category
	 * @param \Rbs\Catalog\Documents\Condition $condition
	 */
	public function downplayProductInCategory(\Rbs\Catalog\Documents\AbstractProduct $product, \Rbs\Catalog\Documents\Category $category, $condition = null)
	{
		$productCategorization = $this->getProductCategorization($product, $category, $condition);
		if (!$productCategorization)
		{
			throw new \RuntimeException("Product to highlight is not in category", 999999);
		}
		$currentPosition = $productCategorization->getPosition();
		if ($currentPosition === 0)
		{
			// Nothing to do
			return;
		}
		$this->downplayProductCategorization($productCategorization);
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 * @throws \Exception
	 */
	public function downplayProductCategorization($productCategorization)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		$category = $productCategorization->getCategory();
		$condition = $productCategorization->getCondition();
		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
		$updateQuery = $this->getCommerceServices()->getApplicationServices()->getDbProvider()->getNewStatementBuilder();
		$fb = $updateQuery->getFragmentBuilder();
		$positionColumn = $fb->getDocumentColumn('position');
		$conditionId = ($condition) ? $condition->getId() : 0;
		$where = $fb->logicAnd(
			$fb->lt($fb->getDocumentColumn('position'), $fb->number(0)),
			$fb->eq($fb->getDocumentColumn('category'), $fb->number($category->getId())),
			$fb->eq($fb->getDocumentColumn('condition'), $fb->number($conditionId))
		);

		try
		{
			$tm->begin();
			$updateQuery->update($fb->getDocumentTable($productCategorization->getDocumentModel()->getRootName()));
			$updateQuery->assign($positionColumn, $fb->addition($positionColumn, $fb->number(1)));
			$updateQuery->where($fb->logicAnd($where,
				$fb->lte($positionColumn, $fb->number($productCategorization->getPosition()))));
			$updateQuery->updateQuery()->execute();
			$productCategorization->setPosition(0);
			$productCategorization->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 * @param $before
	 * @throws \Exception
	 */
	public function highlightProductCategorization($productCategorization, $beforeProductCategorization = null)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		$category = $productCategorization->getCategory();
		$condition = $productCategorization->getCondition();
		$currentPosition = $productCategorization->getPosition();
		$atPosition = -1;
		if ($beforeProductCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization)
		{
			$beforePosition = $beforeProductCategorization->getPosition();
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
			$fb->eq($fb->getDocumentColumn('category'), $fb->number($category->getId())),
			$fb->eq($fb->getDocumentColumn('condition'), $fb->number($conditionId))
		);

		$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
		if ($currentPosition == 0)
		{
			try
			{
				$tm->begin();
				$updateQuery->update($fb->getDocumentTable($productCategorization->getDocumentModel()->getRootName()));
				$updateQuery->assign($positionColumn, $fb->subtraction($positionColumn, $fb->number(1)));
				$updateQuery->where($fb->logicAnd($where, $fb->lte($positionColumn, $fb->number($atPosition))));
				$updateQuery->updateQuery()->execute();
				$productCategorization->setPosition($atPosition);
				$productCategorization->save();
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
				$updateQuery->update($fb->getDocumentTable($productCategorization->getDocumentModel()->getRootName()));
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
				$productCategorization->setPosition($atPosition);
				$productCategorization->save();
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
	 * @param \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 * @throws \RuntimeException
	 * @throws \Exception
	 */
	public function moveProductCategorizationDown($productCategorization)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
						->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		if ($productCategorization->getPosition() == 0)
		{
			// Nothing to do
			return;
		}
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductCategorization');
		$condition = $productCategorization->getCondition();

		$query->andPredicates(
			$query->eq('category', $productCategorization->getCategory()),
			$query->eq('condition', $condition),
			$query->gt('position', $query->getFragmentBuilder()->number($productCategorization->getPosition())),
			$query->lt('position', $query->getFragmentBuilder()->number(0))
		);

		$query->addOrder('position', true);
		/* @var $downCat \Rbs\Catalog\Documents\ProductCategorization */
		$downCat = $query->getFirstDocument();
		if ($downCat)
		{
			$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$toPosition = $downCat->getPosition();
				$fromPosition = $productCategorization->getPosition();
				$productCategorization->setPosition($toPosition);
				$downCat->setPosition($fromPosition);
				$productCategorization->save();
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
	 * @param \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 * @throws \RuntimeException
	 */
	public function moveProductCategorizationUp($productCategorization)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		if ($productCategorization->getPosition() == 0)
		{
			// Nothing to do
			return;
		}
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductCategorization');
		$condition = $productCategorization->getCondition();
		$query->andPredicates(
			$query->eq('category', $productCategorization->getCategory()),
			$query->eq('condition', $condition),
			$query->lt('position', $query->getFragmentBuilder()->number($productCategorization->getPosition()))
		);

		$query->addOrder('position', false);
		/* @var $upCat \Rbs\Catalog\Documents\ProductCategorization */
		$upCat = $query->getFirstDocument();
		if ($upCat)
		{
			$tm = $this->getCommerceServices()->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();
				$toPosition = $upCat->getPosition();
				$fromPosition = $productCategorization->getPosition();
				$productCategorization->setPosition($toPosition);
				$upCat->setPosition($fromPosition);
				$productCategorization->save();
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
	 * @param \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 * @throws \RuntimeException
	 */
	public function highlightProductCategorizationTop($productCategorization)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		$query = new \Change\Documents\Query\Query($this->getCommerceServices()->getDocumentServices(), 'Rbs_Catalog_ProductCategorization');
		$condition = $productCategorization->getCondition();
		$query->andPredicates(
			$query->eq('category', $productCategorization->getCategory()),
			$query->eq('condition', $condition),
			$query->lt('position', $query->getFragmentBuilder()->number(0))
		);
		$query->addOrder('position', true);
		$topProductCategorization = $query->getFirstDocument();
		if ($topProductCategorization == null)
		{
			return;
		}
		if ($topProductCategorization->getId() == $productCategorization->getId())
		{
			return;
		}
		$this->highlightProductCategorization($productCategorization, $topProductCategorization);
	}

	/**
	 * @param \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 * @throws \RuntimeException
	 */
	public function highlightProductCategorizationBottom($productCategorization)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		$this->highlightProductCategorization($productCategorization);
	}

	/**
	 * @param  \Rbs\Catalog\Documents\ProductCategorization|integer $productCategorization
	 */
	public function deleteProductCategorization($productCategorization)
	{
		if (is_numeric($productCategorization))
		{
			$productCategorization = $this->getCommerceServices()
				->getDocumentServices()->getDocumentManager()->getDocumentInstance($productCategorization);
		}
		if (!($productCategorization instanceof \Rbs\Catalog\Documents\ProductCategorization))
		{
			throw new \RuntimeException("Invalid Product Categorization Identifier", 999999);
		}
		if ($productCategorization->isHighlighted())
		{
			$this->downplayProductCategorization($productCategorization);
		}
		$productCategorization->delete();
	}
}