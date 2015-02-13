<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Db;

/**
* @name \Rbs\Storeshipping\Db\StockQueries
*/
class StockQueries
{
	const STORE_STOCK_TABLE = 'rbs_storeshipping_dat_store_stock';

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	function __construct(\Change\Db\DbProvider $dbProvider)
	{
		$this->dbProvider = $dbProvider;
	}

	/**
	 * @param integer $skuId
	 * @param integer $minLevel
	 * @return integer
	 */
	public function countStoreForSkuId($skuId, $minLevel = 1)
	{
		$qb = $this->dbProvider->getNewQueryBuilder('StockQueries::countStoreForSkuId');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->func('COUNT', $fb->column('store_id')), 'count'))
				->from($fb->table(static::STORE_STOCK_TABLE))
				->where(
					$fb->logicAnd(
						$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
						$fb->gte($fb->column('level'), $fb->integerParameter('minLevel'))
					)
				);
		}
		$select = $qb->query();
		$select->bindParameter('skuId', $skuId);
		$select->bindParameter('minLevel', $minLevel);
		return intval($select->getFirstResult($select->getRowsConverter()->addIntCol('count')->singleColumn('count')));
	}

	/**
	 * @param integer $skuId
	 * @param integer $minLevel
	 * @param integer[] $allowedStoreIds
	 * @return integer[]
	 */
	public function storeIdsForSkuId($skuId, $minLevel = 1, array $allowedStoreIds = null)
	{
		$qb = $this->dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->select($fb->column('store_id'))
			->from($fb->table(static::STORE_STOCK_TABLE))
			->orderDesc($fb->column('level'))
			->orderAsc($fb->column('store_id'));

		if ($allowedStoreIds) {
			$qb->where($fb->logicAnd($fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
				$fb->gte($fb->column('level'), $fb->integerParameter('minLevel')),
				$fb->in($fb->column('store_id'), $allowedStoreIds)));
		} else {
			$qb->where($fb->logicAnd($fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
				$fb->gte($fb->column('level'), $fb->integerParameter('minLevel'))));
		}

		$select = $qb->query();
		$select->bindParameter('skuId', $skuId);
		$select->bindParameter('minLevel', $minLevel);
		return $select->getResults($select->getRowsConverter()->addIntCol('store_id')->singleColumn('store_id'));
	}

	/**
	 * @param integer[] $skuIds
	 * @param integer|integer[] $minLevels
	 * @param bool $hasAllSkuIds
	 * @param integer[] $allowedStoreIds
	 * @return integer[]
	 */
	public function storeIdsForSkuIds(array $skuIds, $minLevels = 1, $hasAllSkuIds = true, array $allowedStoreIds = null)
	{
		if (!count($skuIds))
		{
			return [];
		}
		elseif (count($skuIds) == 1)
		{
			return $this->storeIdsForSkuId($skuIds[0], is_int($minLevels) ? $minLevels : $minLevels[0], $allowedStoreIds);
		}

		if (is_int($minLevels))
		{
			$minLevel = $minLevels;
			$minLevels = array_fill(0, count($skuIds), $minLevels);
		}
		else
		{
			$minLevel = min($minLevels);
		}
		$skuLvl = array_combine($skuIds, $minLevels);

		$qb = $this->dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('store_id'), $fb->column('sku_id'), $fb->column('level'))
			->from($fb->table(static::STORE_STOCK_TABLE));

		if ($allowedStoreIds) {
			$qb->where($fb->logicAnd(
				$fb->in($fb->column('sku_id'), $skuIds),
				$fb->gte($fb->column('level'), $fb->integerParameter('minLevel')),
				$fb->in($fb->column('store_id'), $allowedStoreIds)));
		} else {
			$qb->where($fb->logicAnd(
				$fb->in($fb->column('sku_id'), $skuIds),
				$fb->gte($fb->column('level'), $fb->integerParameter('minLevel'))));
		}

		$select = $qb->query();
		$select->bindParameter('minLevel', $minLevel);
		$rows = $select->getResults($select->getRowsConverter()->addIntCol('store_id', 'sku_id', 'level'));

		if (!count($rows))
		{
			return [];
		}
		$stores = [];
		foreach ($rows as $row)
		{
			if ($row['level'] >= $skuLvl[$row['sku_id']])
			{
				$stores[$row['store_id']][$row['sku_id']] = $row['level'];
			}
		}

		if ($hasAllSkuIds)
		{
			$countSku = count($skuIds);
			$stores = array_filter($stores, function($data) use($countSku) {return count($data) == $countSku;});
		}

		return array_keys($stores);
	}

	/**
	 * @param $storeId
	 * @param array $skuIds
	 * @param $minLevels
	 * @return boolean
	 */
	public function validateStockLevel($storeId, array $skuIds, $minLevels)
	{
		$countSku = count($skuIds);
		if (!$storeId || !$countSku)
		{
			return false;
		}
		if (is_int($minLevels))
		{
			$minLevel = $minLevels;
			$minLevels = array_fill(0, $countSku, $minLevels);
		}
		else
		{
			$minLevel = min($minLevels);
		}
		$skuLvl = array_combine($skuIds, $minLevels);

		$qb = $this->dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('sku_id'), $fb->column('level'))
			->from($fb->table(static::STORE_STOCK_TABLE))
			->where(
				$fb->logicAnd(
					$fb->eq($fb->column('store_id'), $fb->number($storeId)),
					$fb->in($fb->column('sku_id'), $skuIds),
					$fb->gte($fb->column('level'), $fb->integerParameter('minLevel'))
				)
			);
		$select = $qb->query();
		$select->bindParameter('minLevel', $minLevel);
		$rows = $select->getResults($select->getRowsConverter()->addIntCol('sku_id', 'level')->indexBy('sku_id')->singleColumn('level'));

		if (count($rows) != $countSku)
		{
			return false;
		}
		foreach ($rows as $skuId => $level)
		{
			if ($skuLvl[$skuId] > $level)
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Need Transaction
	 * @param integer $storeId
	 * @param integer $skuId
	 * @param integer $level
	 * @param float $price
	 */
	public function insertStock($storeId, $skuId, $level = 1, $price = null)
	{
		$qb = $this->dbProvider->getNewStatementBuilder('StockQueries::insertStock');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table(static::STORE_STOCK_TABLE),
				$fb->column('store_id'), $fb->column('sku_id'),
				$fb->column('level'), $fb->column('price'))
				->addValues($fb->integerParameter('storeId'), $fb->integerParameter('skuId'),
					$fb->integerParameter('level'), $fb->decimalParameter('price'));

		}
		$insert = $qb->insertQuery();

		$insert->bindParameter('storeId', $storeId);
		$insert->bindParameter('skuId', $skuId);
		$insert->bindParameter('level', $level);
		$insert->bindParameter('price', $price);

		$insert->execute();
	}
}