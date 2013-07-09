<?php
namespace Rbs\Catalog\Documents;

/**
 * @name \Rbs\Catalog\Documents\Category
 */
class Category extends \Compilation\Rbs\Catalog\Documents\Category
{
	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getSection() ? $this->getSection()->getTitle() : null;
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		// Do nothing.
		return $this;
	}

	/**
	 * @param integer $conditionId
	 * @return integer
	 */
	public function countProducts($conditionId)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->func('count', $fb->column('product_id')), 'count'))
			->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		return intval($sq->getFirstResult($sq->getRowsConverter()->addIntCol('count')));
	}

	/**
	 * @param integer $conditionId
	 * @param integer $offset
	 * @param integer $limit
	 * @return array a collection of rows containing 'product_id' and 'priority'.
	 */
	public function getProductList($conditionId, $offset, $limit)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('product_id'), $fb->column('priority'))->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			))
			->orderDesc($fb->column('priority'));
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		$sq->setStartIndex($offset)->setMaxResults($limit);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('product_id', 'priority'));
	}

	/**
	 * @param integer $conditionId
	 * @param integer[] $productIds
	 * @param integer[]|integer $priorities
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function setProductIds($conditionId, $productIds, $priorities)
	{
		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$this->removeAllProductIds($conditionId);
			$this->addProductIds($conditionId, $productIds, $priorities);

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param integer $conditionId
	 * @param integer[] $productIds
	 * @param integer[]|integer $priorities
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function addProductIds($conditionId, $productIds, $priorities)
	{
		if (is_array($priorities))
		{
			if (count($priorities) != count($productIds))
			{
				throw new \RuntimeException('Argument 2 has not the same size than argument 1.', 999999);
			}
		}
		elseif (!is_numeric($priorities))
		{
			throw new \RuntimeException('Argument 2 should be an array or an integer.', 999999);
		}
		else
		{
			$priorities = array_fill(0, count($productIds), intval($priorities));
		}

		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$allIds = $this->getAllProductIds($conditionId);
			$stmt = $as->getDbProvider()->getNewStatementBuilder();
			$fb = $stmt->getFragmentBuilder();

			// Create insert statement.
			$stmt->insert($fb->table('rbs_catalog_category_products'),
				$fb->column('category_id'),
				$fb->column('product_id'),
				$fb->column('condition_id'),
				$fb->column('priority')
			);
			$stmt->addValues(
				$fb->integerParameter('categoryId'),
				$fb->integerParameter('productId'),
				$fb->integerParameter('conditionId'),
				$fb->integerParameter('priority')
			);
			$iq = $stmt->insertQuery();

			foreach ($productIds as $index => $productId)
			{
				if (in_array($productId, $allIds))
				{
					continue;
				}

				$iq->bindParameter('categoryId', $this->getId());
				$iq->bindParameter('productId', $productId);
				$iq->bindParameter('conditionId', $conditionId);
				$iq->bindParameter('priority', $priorities[$index]);
				$iq->execute();
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
	public function removeAllProductIds($conditionId)
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
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
			$dq = $stmt->deleteQuery();
			$dq->bindParameter('categoryId', $this->getId());
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
	 * @param integer[] $productIds
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	public function removeProductIds($conditionId, $productIds)
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
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
			$dq = $stmt->deleteQuery();

			foreach ($productIds as $productId)
			{
				$dq->bindParameter('categoryId', $this->getId());
				$dq->bindParameter('productId', $productId);
				$dq->bindParameter('conditionId', $conditionId);
				$dq->execute();
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
	 * @return integer[]
	 */
	protected function getAllProductIds($conditionId)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('product_id'))->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('product_id'));
	}
}