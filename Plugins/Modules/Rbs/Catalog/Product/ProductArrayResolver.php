<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Product;

/**
* @name \Rbs\Catalog\Product\ProductArrayResolver
*/
class ProductArrayResolver
{
	/**
	 * @var \Rbs\Catalog\Documents\ProductList
	 */
	protected $list;

	/**
	 * @var string
	 */
	protected $defaultSortBy;

	/**
	 * @var array
	 */
	protected $productArray;

	/**
	 * @var integer
	 */
	protected $totalCount;

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $offset;

	/**
	 * @var integer
	 */
	protected $conditionId;

	/**
	 * @var boolean
	 */
	protected $showUnavailable;

	/**
	 * @var integer
	 */
	protected $billingAreaId;

	/**
	 * @var integer
	 */
	protected $webStoreId;

	/**
	 * @var string
	 */
	protected $sortBy;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @var \Rbs\Stock\StockManager
	 */
	protected $stockManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @return integer
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @return integer
	 */
	public function getLimit()
	{
		return $this->limit;
	}

	/**
	 * @return integer
	 */
	public function getTotalCount()
	{
		if ($this->totalCount === null) {
			$this->resolve();
		}
		return $this->totalCount;
	}

	/**
	 * @return array
	 */
	public function getProductArray()
	{
		if ($this->productArray === null) {
			$this->resolve();
		}
		return $this->productArray;
	}

	public function __construct(\Rbs\Catalog\Documents\ProductList $list, array $context,
		\Change\Documents\DocumentManager $documentManager,
		\Rbs\Stock\StockManager $stockManager,
		\Change\Db\DbProvider $dbProvider)
	{
		$this->list = $list;
		$this->defaultSortBy = $list->getProductSortOrder() . '.' . $list->getProductSortDirection();
		$this->documentManager = $documentManager;
		$this->stockManager = $stockManager;
		$this->dbProvider = $dbProvider;

		if (!isset($context['data']) || !is_array($context['data']))
		{
			$data = [];
		}
		else
		{
			$data = $context['data'];
		}

		$pagination = isset($context['pagination']) && is_array($context['pagination']) ? $context['pagination'] : [];
		$this->offset = isset($pagination['offset']) ? intval($pagination['offset']) : 0;
		$this->limit = isset($pagination['limit']) ? intval($pagination['limit']) : 100;
		$this->conditionId = isset($data['conditionId']) ? intval($data['conditionId']) : 0;
		$this->showUnavailable = isset($data['showUnavailable']) ? ($data['showUnavailable'] == true) : true;
		$this->billingAreaId = isset($data['billingAreaId']) ? intval($data['billingAreaId']) : 0;
		$this->webStoreId = isset($data['webStoreId']) ? intval($data['webStoreId']) : 0;
		$this->sortBy = isset($data['sortBy']) ? strval($data['sortBy']) : null;
	}

	protected function resolve()
	{
		$this->productArray = [];
		$this->totalCount = 0;

		$query = $this->documentManager->getNewQuery('Rbs_Catalog_Product', $this->documentManager->getLCID());
		$predicates = [$query->published()];

		if (!$this->showUnavailable)
		{
			$predicates[] = $this->stockManager->getProductAvailabilityRestriction($this->dbProvider, $query->getColumn('id'));
		}
		$query->andPredicates($predicates);

		$subQuery = $query->getModelBuilder('Rbs_Catalog_ProductListItem', 'product');
		$predicates = [
			$subQuery->eq('productList', $this->list->getId()),
			$subQuery->eq('condition', $this->conditionId),
			$subQuery->activated()
		];
		$subQuery->andPredicates($predicates);

		$queryBuilder = $this->addSort($query, $subQuery);
		$selectQuery = $queryBuilder->query();

		$this->totalCount = $this->getCountDocuments($query, $selectQuery);
		if ($this->totalCount)
		{
			$limit = $this->limit;
			$offset = $this->offset;
			if ($offset >= $this->totalCount )
			{
				$this->offset = $offset = 0;
			}
			$query->setQueryParameters($selectQuery);
			$selectQuery->setStartIndex($offset)->setMaxResults($limit);

			$this->productArray = $selectQuery->getResults($selectQuery->getRowsConverter()->addIntCol('id'));
		}
	}

	/**
	 * @param \Change\Documents\Query\Query $query
	 * @param \Change\Db\Query\SelectQuery $selectQuery
	 * @return integer
	 */
	public function getCountDocuments($query, $selectQuery)
	{
		$qb = $query->getDbProvider()->getNewQueryBuilder();
		$fragmentBuilder = $qb->getFragmentBuilder();
		$qb->select($fragmentBuilder->alias($fragmentBuilder->func('count', '*'), 'rowCount'));
		$qb->from($fragmentBuilder->alias($fragmentBuilder->subQuery($selectQuery), '_tmpCount'));
		$selectCount = $qb->query();
		$query->setQueryParameters($selectCount);
		$count = $selectCount->getFirstResult($selectCount->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount'));
		return $count;
	}

	/**
	 * @param \Change\Documents\Query\Query $query
	 * @param \Change\Documents\Query\ChildBuilder $subQuery
	 * @return \Change\Db\Query\Builder
	 */
	protected function addSort($query, $subQuery)
	{
		$dbq = null;

		if ($this->sortBy == null)
		{
			$subQuery->addOrder('position', true);
			list($sortName, $sortDir) = explode('.', $this->defaultSortBy);
		}
		else
		{
			list($sortName, $sortDir) = explode('.', $this->sortBy);
		}

		if ($this->webStoreId && $this->billingAreaId && in_array($sortName, ['dateAdded', 'price', 'level']))
		{
			$dbq = $query->dbQueryBuilder();
			$fb = $dbq->getFragmentBuilder();
			$tableIdentifier = $fb->identifier('tb_sort');
			$join = $fb->logicAnd(
				$fb->eq($subQuery->getColumn('id'), $fb->column('listitem_id', $tableIdentifier)),
				$fb->eq($fb->column('store_id', $tableIdentifier), $fb->number($this->webStoreId)),
				$fb->eq($fb->column('billing_area_id', $tableIdentifier), $fb->number($this->billingAreaId))
			);
			$dbq->innerJoin($fb->alias($fb->table('rbs_catalog_dat_productlistitem'), $tableIdentifier), $join);

			if ($sortName == 'level')
			{
				if ($sortDir != 'desc')
				{
					$dbq->orderAsc($fb->column('sort_level', $tableIdentifier));
				}
				else
				{
					$dbq->orderDesc($fb->column('sort_level', $tableIdentifier));
				}
			}
			elseif ($sortName == 'price')
			{
				if ($sortDir != 'desc')
				{
					$dbq->orderAsc($fb->column('sort_price', $tableIdentifier));
				}
				else
				{
					$dbq->orderDesc($fb->column('sort_price', $tableIdentifier));
				}
			}
			else
			{
				if ($sortDir != 'desc')
				{
					$dbq->orderAsc($fb->column('sort_date', $tableIdentifier));
				}
				else
				{
					$dbq->orderDesc($fb->column('sort_date', $tableIdentifier));
				}
			}
		}
		elseif ($sortName == 'dateAdded')
		{
			$subQuery->addOrder('creationDate', $sortDir != 'desc');
		}
		else
		{
			$query->addOrder('title', $sortDir != 'desc');
		}

		if ($dbq === null)
		{
			$dbq = $query->dbQueryBuilder();
		}
		$dbq->addColumn($dbq->getFragmentBuilder()->alias($query->getColumn('id'), 'id'));
		return $dbq;
	}
} 