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
	 * @return int a collection of rows containing 'product_id' and 'priority'.
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

		switch ($this->getProductSortOrder())
		{
			case 'label':
				$qb->innerJoin($fb->table('rbs_catalog_doc_abstractproduct'), $fb->eq(
					$fb->column('product_id', 'rbs_catalog_category_products'),
					$fb->column('document_id', 'rbs_catalog_doc_abstractproduct')
				));
				$qb->{'order' . ucfirst($this->getProductSortDirection())}($fb->column('label'));
				break;

			case 'title':
				$qb->innerJoin($fb->table('rbs_catalog_doc_abstractproduct_i18n'),
					$fb->eq(
						$fb->column('product_id', 'rbs_catalog_category_products'),
						$fb->column('document_id', 'rbs_catalog_doc_abstractproduct_i18n')
					),
					$fb->eq(
						$fb->column('lcid', 'rbs_catalog_doc_abstractproduct_i18n'),
						$fb->string($this->getDocumentManager()->getLCID())
					)
				);
				$qb->{'order' . ucfirst($this->getProductSortDirection())}($fb->column('title'));
				break;

			default:
				$this->getApplicationServices()->getLogging()
					->warn(__METHOD__ . ' Unknown product sort order: ' . $this->getProductSortOrder());
				break;
		}
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
	 * @param integer[]|integer|'top' $priorities
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
		elseif ($priorities === 'top')
		{
			$priorities = array_fill(0, count($productIds), intval($this->getTopPriority($conditionId)));
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

			// Create insert statement.
			$stmt = $as->getDbProvider()->getNewStatementBuilder('Category.addProductIds.insert');
			if (!$stmt->isCached())
			{
				$fb = $stmt->getFragmentBuilder();
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
			}
			$iq = $stmt->insertQuery();

			// Create update statement.
			$stmt2 = $as->getDbProvider()->getNewStatementBuilder('Category.addProductIds.update');
			if (!$stmt2->isCached())
			{
				$fb = $stmt->getFragmentBuilder();
				$stmt->update($fb->table('rbs_catalog_category_products'));
				$stmt->assign($fb->column('priority'), $fb->integerParameter('priority'));
				$stmt->where($fb->logicAnd(
					$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
					$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
					$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
				));
			}
			$uq = $stmt->updateQuery();

			foreach ($productIds as $index => $productId)
			{
				$priority = $priorities[$index];
				if (in_array($productId, $allIds))
				{
					$q = $uq;
					$oldPriority = $this->getProductPriority($conditionId, $productId);
					if ($oldPriority > 0)
					{
						$this->removeProductPlace($conditionId, $oldPriority);
					}
				}
				else
				{
					$q = $iq;
				}

				if ($priorities[$index] > 0 && !$this->isPlaceFree($conditionId, $priority))
				{
					$this->createProductPlace($conditionId, $priority);
				}

				$q->bindParameter('categoryId', $this->getId());
				$q->bindParameter('productId', $productId);
				$q->bindParameter('conditionId', $conditionId);
				$q->bindParameter('priority', $priority);
				$q->execute();
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
				$oldPriority = $this->getProductPriority($conditionId, $productId);
				if ($oldPriority > 0)
				{
					$this->removeProductPlace($conditionId, $oldPriority);
				}

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
	 * @param integer $productId
	 * @return integer|null
	 */
	protected function getProductPriority($conditionId, $productId)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder('Category.getProductPriority');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('product_id'), $fb->column('priority'))->from($fb->table('rbs_catalog_category_products'))
				->where($fb->logicAnd(
					$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
					$fb->eq($fb->column('product_id'), $fb->integerParameter('productId')),
					$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
				))
				->orderDesc($fb->column('priority')
			);
		}
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('productId', $productId);
		$sq->bindParameter('conditionId', $conditionId);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('priority'));
	}

	/**
	 * @param integer $conditionId
	 * @param integer $priority
	 * @return boolean
	 */
	protected function isPlaceFree($conditionId, $priority)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('priority'))
			->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId')),
				$fb->eq($fb->column('priority'), $fb->integerParameter('priority'))
			));
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		$sq->bindParameter('priority', $priority);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('priority')) !== $priority;
	}

	/**
	 * @param integer $conditionId
	 * @param integer $priority
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function createProductPlace($conditionId, $priority)
	{
		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$stmt = $as->getDbProvider()->getNewStatementBuilder('Category.createProductPlace');
			if (!$stmt->isCached())
			{
				$fb = $stmt->getFragmentBuilder();
				$stmt->update($fb->table('rbs_catalog_category_products'));
				$stmt->assign($fb->column('priority'), $fb->addition($fb->column('priority'), $fb->number(1)));
				$stmt->where($fb->logicAnd(
					$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
					$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId')),
					$fb->gte($fb->column('priority'), $fb->integerParameter('priority'))
				));
			}
			$uq = $stmt->updateQuery();
			$uq->bindParameter('categoryId', $this->getId());
			$uq->bindParameter('conditionId', $conditionId);
			$uq->bindParameter('priority', $priority);
			$uq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param integer $conditionId
	 * @param integer $priority
	 * @throws \Exception
	 * @throws \RuntimeException
	 */
	protected function removeProductPlace($conditionId, $priority)
	{
		$as = $this->getApplicationServices();
		$transactionManager = $as->getTransactionManager();
		try
		{
			$transactionManager->begin();

			$stmt = $as->getDbProvider()->getNewStatementBuilder('Category.removeProductPlace');
			if (!$stmt->isCached())
			{
				$fb = $stmt->getFragmentBuilder();
				$stmt->update($fb->table('rbs_catalog_category_products'));
				$stmt->assign($fb->column('priority'), $fb->addition($fb->column('priority'), $fb->number(-1)));
				$stmt->where($fb->logicAnd(
					$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
					$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId')),
					$fb->gt($fb->column('priority'), $fb->integerParameter('priority'))
				));
			}
			$uq = $stmt->updateQuery();
			$uq->bindParameter('categoryId', $this->getId());
			$uq->bindParameter('conditionId', $conditionId);
			$uq->bindParameter('priority', $priority);
			$uq->execute();

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
		$qb->select($fb->column('product_id'))
			->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		return $sq->getResults($sq->getRowsConverter()->addIntCol('product_id'));
	}

	/**
	 * @param integer $conditionId
	 * @return integer
	 */
	protected function getTopPriority($conditionId)
	{
		$as = $this->getApplicationServices();
		$qb = $as->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->func('max')->addArgument($fb->column('priority')), 'topPriority'))
			->from($fb->table('rbs_catalog_category_products'))
			->where($fb->logicAnd(
				$fb->eq($fb->column('category_id'), $fb->integerParameter('categoryId')),
				$fb->eq($fb->column('condition_id'), $fb->integerParameter('conditionId'))
			));
		$sq = $qb->query();
		$sq->bindParameter('categoryId', $this->getId());
		$sq->bindParameter('conditionId', $conditionId);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('topPriority'));
	}
}