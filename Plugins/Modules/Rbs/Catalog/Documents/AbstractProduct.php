<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\AbstractProduct
 */
class AbstractProduct extends \Compilation\Rbs\Catalog\Documents\AbstractProduct
{
	/**
	 * @return \Rbs\Media\Documents\Image|null
	 */
	public function getFirstVisual()
	{
		return $this->getVisuals()[0];
	}

	/**
	 * @param integer $conditionId
	 * @return integer
	 */
	public function countCategories($conditionId)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->func('count', $fb->column('category_id')), 'count'))
			->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
		$sq = $qb->query();
		$sq->bindParameter('productId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		return intval($sq->getFirstResult($sq->getRowsConverter()->addIntCol('count')));
	}

	/**
	 * @param integer $conditionId
	 * @param integer $offset
	 * @param integer $limit
	 * @return array a collection of rows containing 'category_id' and 'priority'.
	 */
	public function getCategoryList($conditionId, $offset, $limit)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('category_id'), $fb->column('priority'))->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			))
			->orderDesc($fb->column('category_id'));
		$sq = $qb->query();
		$sq->bindParameter('productId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		$sq->setStartIndex($offset)->setMaxResults($limit);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('category_id', 'priority'));
	}

	/**
	 * @param integer $conditionId
	 * @param integer[] $categoryIds
	 * @param integer[]|integer $priorities
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function setCategoryIds($conditionId, $categoryIds, $priorities)
	{
		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$this->removeAllCategoryIds($conditionId);
			$this->addCategoryIds($conditionId, $categoryIds, $priorities);

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param integer $conditionId
	 * @param integer[] $categoryIds
	 * @param integer[]|integer $priorities
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function addCategoryIds($conditionId, $categoryIds, $priorities)
	{
		if (is_array($priorities))
		{
			if (count($priorities) != count($categoryIds))
			{
				throw new \RuntimeException('Argument 2 has not the same size than argument 1.', 999999);
			}
		}

		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$dm = $this->getDocumentManager();
			foreach ($categoryIds as $index => $categoryId)
			{
				$category = $dm->getDocumentInstance($categoryId);
				if ($category instanceof \Rbs\Catalog\Documents\Category)
				{
					$priority = (is_array($priorities)) ? $priorities[$index] : $priorities;
					$category->addProductIds($conditionId, array($this->getId()), array($priority));
				}
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param integer $conditionId
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function removeAllCategoryIds($conditionId)
	{
		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$stmt = $as->getDbProvider()->getNewStatementBuilder();
			$fb = $stmt->getFragmentBuilder();
			$stmt->delete($fb->table('rbs_catalog_category_products'));
			$stmt->where($fb->logicAnd(
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
			$dq = $stmt->deleteQuery();
			$dq->bindParameter('productId', $this->getId());
			$dq->bindParameter('conditionId', $conditionId);
			$dq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param integer $conditionId
	 * @param integer[] $categoryIds
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function removeCategoryIds($conditionId, $categoryIds)
	{
		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$dm = $this->getDocumentManager();
			foreach ($categoryIds as $categoryId)
			{
				/* @var $category \Rbs\Catalog\Documents\Category */
				$category = $dm->getDocumentInstance($categoryId);
				if ($category instanceof \Rbs\Catalog\Documents\Category)
				{
					$category->removeProductIds($conditionId, array($this->getId()));
				}
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}
}