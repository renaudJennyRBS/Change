<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock;

/**
 * @name \Rbs\Stock\StockManager
 */
class StockManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'StockManager';

	const INVENTORY_UNIT_PIECE = 0;

	const UNLIMITED_LEVEL = 1000000;

	const THRESHOLD_AVAILABLE = 'AVAILABLE';

	const THRESHOLD_UNAVAILABLE = 'UNAVAILABLE';

	/**
	 * @var \Rbs\Commerce\Std\Context
	 */
	protected $context;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;


	/**
	 * @var boolean|null
	 */
	protected $disableReservation = null;

	/**
	 * @var boolean|null
	 */
	protected $disableMovement = null;


	protected $availableWarehouseIds = null;


	protected $storeWarehouseId = [];

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
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/Commerce/Events/StockManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getInventoryEntry', [$this, 'onDefaultGetInventoryEntry'], 5);
		$eventManager->attach('getInventoryEntries', [$this, 'onDefaultGetInventoryEntries'], 5);
		$eventManager->attach('setInventory', [$this, 'onDefaultSetInventory'], 5);
		$eventManager->attach('getInventoryMovementsByTarget', [$this, 'onDefaultGetInventoryMovementsByTarget'], 5);
		$eventManager->attach('getInventoryMovementsBySku', [$this, 'onDefaultGetInventoryMovementsBySku'], 5);
		$eventManager->attach('countInventoryMovementsBySku', [$this, 'onDefaultCountInventoryMovementsBySku'], 5);
		$eventManager->attach('getInventoryMovementsInfosBySkuGroupByWarehouse',
			[$this, 'onDefaultGetInventoryMovementsInfosBySkuGroupByWarehouse'], 5);

		$eventManager->attach('getValueOfMovementsBySku', [$this, 'onDefaultGetValueOfMovementsBySku'], 5);
		$eventManager->attach('consolidateInventoryEntry', [$this, 'onDefaultConsolidateInventoryEntry'], 5);
		$eventManager->attach('addInventoryMovement', [$this, 'onDefaultAddInventoryMovement'], 5);
		$eventManager->attach('deleteInventoryMovementById', [$this, 'onDefaultDeleteInventoryMovementById'], 5);
		$eventManager->attach('getInventoryLevel', [$this, 'onDefaultGetInventoryLevel'], 5);
		$eventManager->attach('getInventoryLevelForManySku', [$this, 'onDefaultGetInventoryLevelForManySku'], 5);

		$eventManager->attach('getInventoryThresholdTitle', [$this, 'onDefaultGetInventoryThresholdTitle'], 5);

		$eventManager->attach('setReservations', [$this, 'onDefaultSetReservations'], 5);
		$eventManager->attach('getReservationsInfosBySkuGroupByStoreAndStatus',
			[$this, 'onDefaultGetReservationsInfosBySkuGroupByStoreAndStatus'], 5);
		$eventManager->attach('getReservationsByTarget', [$this, 'onDefaultGetReservationsByTarget'], 5);
		$eventManager->attach('cleanupReservations', [$this, 'onDefaultCleanupReservations'], 5);
		$eventManager->attach('unsetReservations', [$this, 'onDefaultUnsetReservations'], 5);
		$eventManager->attach('confirmReservations', [$this, 'onDefaultConfirmReservations'], 5);
		$eventManager->attach('transferReservations', [$this, 'onDefaultTransferReservations'], 5);
		$eventManager->attach('decrementReservation', [$this, 'onDefaultDecrementReservation'], 5);
		$eventManager->attach('getReservationsBySku', [$this, 'onDefaultGetReservationsBySku'], 5);
		$eventManager->attach('countReservationsBySku', [$this, 'onDefaultCountReservationsBySku'], 5);

		$eventManager->attach('getSkuByCode', [$this, 'onDefaultGetSkuByCode'], 5);

		$eventManager->attach('getProductAvailability', [$this, 'onDefaultGetProductAvailability'], 5);
	}

	/**
	 * @param boolean $disableMovement
	 * @return $this
	 */
	public function setDisableMovement($disableMovement)
	{
		$this->disableMovement = ($disableMovement == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getDisableMovement()
	{
		if ($this->disableMovement === null)
		{
			$this->setDisableMovement($this->getApplication()->getConfiguration()->getEntry('Rbs/Stock/disableMovement'));
		}
		return $this->disableMovement;
	}

	/**
	 * @param boolean $disableReservation
	 * @return $this
	 */
	public function setDisableReservation($disableReservation)
	{
		$this->disableReservation = ($disableReservation == true);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getDisableReservation()
	{
		if ($this->disableReservation === null)
		{
			$this->setDisableReservation($this->getApplication()->getConfiguration()->getEntry('Rbs/Stock/disableReservation'));
		}
		return $this->disableReservation;
	}

	/**
	 * @param \Rbs\Commerce\Std\Context $context
	 * @return $this
	 */
	public function setContext(\Rbs\Commerce\Std\Context $context)
	{
		$this->context = $context;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Std\Context
	 */
	protected function getContext()
	{
		return $this->context;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
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
	 * @api
	 * @return integer[]
	 */
	public function getAvailableWarehouseIds()
	{
		if ($this->availableWarehouseIds === null)
		{
			$this->availableWarehouseIds = [0];
			$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_Warehouse');
			$query->andPredicates($query->eq('physical', false));
			foreach ($query->getDocumentIds() as $id)
			{
				$this->availableWarehouseIds[] = $id;
			}
		}
		return $this->availableWarehouseIds;
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore|integer $store
	 * @return integer
	 */
	protected function getWarehouseIdByStore($store)
	{
		$storeId = is_numeric($store) ? intval($store) : ($store instanceof \Rbs\Store\Documents\WebStore ? $store->getId() : 0);
		if (isset($this->storeWarehouseId[$storeId]))
		{
			return $this->storeWarehouseId[$storeId];
		}
		if ($storeId > 0)
		{
			$store = $this->getDocumentManager()->getDocumentInstance($storeId);
			if ($store instanceof \Rbs\Store\Documents\WebStore)
			{
				$warehouseId = intval($store->getWarehouseId());
				$this->storeWarehouseId[$storeId] = $warehouseId;
				return $warehouseId;
			}
		}
		return 0;
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\Warehouse|integer|null $warehouse
	 * @return \Rbs\Stock\Documents\InventoryEntry|null
	 */
	public function getInventoryEntry($sku, $warehouse = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['sku' => $sku, 'warehouse' => $warehouse, 'inventoryEntry' => null]);
		$em->trigger('getInventoryEntry', $this, $args);
		if ($args['inventoryEntry'] instanceof \Rbs\Stock\Documents\InventoryEntry)
		{
			return $args['inventoryEntry'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryEntry(\Change\Events\Event $event)
	{
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates(
			$query->eq('sku', $event->getParam('sku')),
			$query->eq('warehouse', $event->getParam('warehouse'))
		);
		$event->setParam('inventoryEntry', $query->getFirstDocument());
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @return \Rbs\Stock\Documents\InventoryEntry[]
	 */
	public function getInventoryEntries($sku)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['sku' => $sku, 'inventoryEntries' => null]);
		$em->trigger('getInventoryEntries', $this, $args);
		if (is_array($args['inventoryEntries']))
		{
			return $args['inventoryEntries'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryEntries(\Change\Events\Event $event)
	{
		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates(
			$query->eq('sku', $event->getParam('sku'))
		);
		$event->setParam('inventoryEntries', $query->getDocuments()->toArray());
	}

	/**
	 * @api
	 * @param integer $level
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\Warehouse|null $warehouse
	 * @throws \Exception
	 * @return \Rbs\Stock\Documents\InventoryEntry
	 */
	public function setInventory($level, $sku, $warehouse = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['level' => $level, 'sku' => $sku, 'warehouse' => $warehouse, 'inventoryEntry' => null]);
		$em->trigger('setInventory', $this, $args);
		return $args['inventoryEntry'];
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultSetInventory(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$warehouse = $event->getParam('warehouse');
		$level = $event->getParam('level');
		$appSrv = $event->getApplicationServices();
		$tm = $appSrv->getTransactionManager();
		try
		{
			$tm->begin();
			$entry = $this->getInventoryEntry($sku, $warehouse);
			if ($entry === null)
			{
				/* @var $entry \Rbs\Stock\Documents\InventoryEntry */
				$entry = $appSrv->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
			}
			$entry->setLevel($level);
			$entry->setSku($sku);
			$entry->setWarehouse($warehouse);
			$entry->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$event->setParam('inventoryEntry', $entry);
	}

	/**
	 * @api
	 * @param string $target
	 * @param \Rbs\Stock\Documents\Warehouse|null $warehouse
	 * @return array
	 */
	public function getInventoryMovementsByTarget($target, $warehouse = null)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['target' => $target, 'warehouse' => $warehouse, 'inventoryMovements' => null]);
			$em->trigger('getInventoryMovementsByTarget', $this, $args);
			if (is_array($args['inventoryMovements']))
			{
				return $args['inventoryMovements'];
			}
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryMovementsByTarget(\Change\Events\Event $event)
	{
		$target = $event->getParam('target');
		$warehouse = $event->getParam('warehouse');
		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder('stock::getInventoryMovements');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('sku_id'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'));
			$qb->from($fb->table('rbs_stock_dat_mvt'));
			$logicAnd = $fb->logicAnd($fb->eq($fb->column('target'), $fb->parameter('target')));
			if ($warehouse instanceof \Rbs\Stock\Documents\Warehouse)
			{
				$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
			}
			$qb->where($logicAnd);
		}
		$query = $qb->query();
		$query->bindParameter('target', $target);
		if ($warehouse instanceof \Rbs\Stock\Documents\Warehouse)
		{
			$query->bindParameter('warehouseId', $warehouse->getId());
		}
		$event->setParam('inventoryMovements',
			$query->getResults($query->getRowsConverter()->addIntCol('sku_id', 'movement', 'warehouse_id')->addDtCol('date')));
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\Warehouse|integer|null $warehouse
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @param string|null $orderCol
	 * @param string|null $orderSort
	 * @return array
	 */
	public function getInventoryMovementsBySku($sku, $warehouse = null, $limit = null, $offset = null, $orderCol = null,
		$orderSort = null)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'warehouse' => $warehouse,
				'limit' => $limit, 'offset' => $offset, 'orderCol' => $orderCol, 'orderSort' => $orderSort,
				'inventoryMovements' => null]);
			$em->trigger('getInventoryMovementsBySku', $this, $args);
			if (is_array($args['inventoryMovements']))
			{
				return $args['inventoryMovements'];
			}
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryMovementsBySku(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$warehouse = $event->getParam('warehouse');
		$limit = $event->getParam('limit');
		$offset = $event->getParam('offset');
		$orderCol = $event->getParam('orderCol');
		$orderSort = $event->getParam('orderSort');

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('id'), $fb->column('target'), $fb->column('movement'), $fb->column('warehouse_id'),
			$fb->column('date'));
		$qb->from($fb->table('rbs_stock_dat_mvt'));
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
		);
		if ($warehouse !== null)
		{
			$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
		}
		$qb->where($logicAnd);

		if ($orderSort !== null && $orderCol !== null)
		{
			if ($orderSort === 'desc')
			{
				$qb->orderDesc($fb->column($orderCol));
			}
			else
			{
				$qb->orderAsc($fb->column($orderCol));
			}
		}

		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($warehouse !== null)
		{
			$warehouseId =
				$warehouse instanceof \Rbs\Stock\Documents\Warehouse ? $warehouse->getId() : intval($warehouse);
			$query->bindParameter('warehouseId', $warehouseId);
		}

		if ($limit !== null && $offset !== null)
		{
			$query->setMaxResults($limit);
			$query->setStartIndex($offset);
		}

		$event->setParam('inventoryMovements', $query->getResults(
			$query->getRowsConverter()->addStrCol('target')->addIntCol('id', 'movement', 'warehouse_id')->addDtCol('date')
		));
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\Warehouse|integer|null $warehouse
	 * @return integer
	 */
	public function countInventoryMovementsBySku($sku, $warehouse = null)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'warehouse' => $warehouse,
				'count' => 0]);
			$em->trigger('countInventoryMovementsBySku', $this, $args);
			return $args['count'];
		}
		return 0;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCountInventoryMovementsBySku(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$warehouse = $event->getParam('warehouse');

		$key = 'stock::countInventoryMovementsBySku';
		if ($warehouse !== null)
		{
			$key = 'stock::countInventoryMovementsBySkuAndWarehouse';
		}

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder($key);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->func('count', '*'), 'rowCount'));
			$qb->from($fb->table('rbs_stock_dat_mvt'));
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
			);
			if ($warehouse !== null)
			{
				$logicAnd->addArgument($fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId')));
			}
			$qb->where($logicAnd);
		}
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($warehouse !== null)
		{
			$warehouseId =
				$warehouse instanceof \Rbs\Stock\Documents\Warehouse ? $warehouse->getId() : intval($warehouse);
			$query->bindParameter('warehouseId', $warehouseId);
		}

		$event->setParam('count',
			$query->getFirstResult($query->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount')));
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @return array|null
	 */
	public function getInventoryMovementsInfosBySkuGroupByWarehouse($sku)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'inventoryMovements' => []]);
			$em->trigger('getInventoryMovementsInfosBySkuGroupByWarehouse', $this, $args);
			return $args['inventoryMovements'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryMovementsInfosBySkuGroupByWarehouse(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$qb = $event->getApplicationServices()->getDbProvider()
			->getNewQueryBuilder('stock::getInventoryMovementsInfosBySkuGroupByWarehouse');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->column('warehouse_id'), 'warehouse'),
				$fb->alias($fb->func('count', '*'), 'count'), $fb->alias($fb->sum($fb->column('movement')), 'movement'));
			$qb->from($fb->table('rbs_stock_dat_mvt'));
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
			);
			$qb->where($logicAnd);
			$qb->group($fb->column('warehouse'));
		}
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		$event->setParam('inventoryMovements',
			$query->getResults($query->getRowsConverter()->addIntCol('count', 'movement', 'warehouse')));
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\Warehouse|integer $warehouse
	 * @return integer
	 */
	public function getValueOfMovementsBySku($sku, $warehouse = null)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'warehouse' => $warehouse, 'value' => 0]);
			$em->trigger('getValueOfMovementsBySku', $this, $args);
			return $args['value'];
		}
		return 0;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetValueOfMovementsBySku(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$warehouse = $event->getParam('warehouse');

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->alias($fb->sum($fb->column('movement')), 'movement'));
		$qb->from($fb->table('rbs_stock_dat_mvt'));
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
			$fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId'))
		);
		$qb->where($logicAnd);
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		$warehouseId = $warehouse instanceof \Rbs\Stock\Documents\Warehouse ? $warehouse->getId() : intval($warehouse);
		$query->bindParameter('warehouseId', $warehouseId);

		$event->setParam('value',
			$query->getFirstResult($query->getRowsConverter()->addIntCol('movement')->singleColumn('movement')));
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param \Rbs\Stock\Documents\InventoryEntry $inventoryEntry
	 */
	public function consolidateInventoryEntry(\Rbs\Stock\Documents\InventoryEntry $inventoryEntry)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['inventoryEntry' => $inventoryEntry]);
			$em->trigger('consolidateInventoryEntry', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultConsolidateInventoryEntry(\Change\Events\Event $event)
	{
		/** @var $inventoryEntry \Rbs\Stock\Documents\InventoryEntry */
		$inventoryEntry = $event->getParam('inventoryEntry');
		if ($inventoryEntry instanceof \Rbs\Stock\Documents\InventoryEntry)
		{
			$warehouseId = $inventoryEntry->getWarehouseId();
			$skuId = $inventoryEntry->getSkuId();
			$valueOfMovementsBySku = $this->getValueOfMovementsBySku($skuId, $warehouseId);
			if ($valueOfMovementsBySku !== 0)
			{
				$qb = $event->getApplicationServices()->getDbProvider()
					->getNewStatementBuilder('stock::consolidateInventoryEntry');
				if (!$qb->isCached())
				{
					$fb = $qb->getFragmentBuilder();
					$qb->delete($fb->table('rbs_stock_dat_mvt'));
					$qb->where(
						$fb->logicAnd(
							$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
							$fb->eq($fb->column('warehouse_id'), $fb->integerParameter('warehouseId'))
						)
					);
				}
				$statement = $qb->deleteQuery();
				$statement->bindParameter('skuId', $skuId);
				$statement->bindParameter('warehouseId', $warehouseId);
				$statement->execute();

				if ($inventoryEntry->getSku() && $inventoryEntry->getSku()->getUnlimitedInventory())
				{
					$inventoryEntry->setLevel(static::UNLIMITED_LEVEL);
				}
				else
				{
					$inventoryEntry->setLevel($inventoryEntry->getLevel() + $valueOfMovementsBySku);
				}
				$inventoryEntry->setValueOfMovements(0);
				$inventoryEntry->save();
			}
		}
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * Positive = receipt, negative = issue
	 * @param integer $amount
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Stock\Documents\Warehouse|null $warehouse
	 * @param \DateTime|null $date
	 * @param string $target
	 * @throws \Exception
	 * @return integer
	 */
	public function addInventoryMovement($amount, $sku, $warehouse = null, $date = null, $target = null)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['amount' => $amount, 'sku' => $sku, 'warehouse' => $warehouse,
				'date' => $date, 'target' => $target, 'movementId' => 0]);
			$em->trigger('addInventoryMovement', $this, $args);
			return $args['movementId'];
		}
		return 0;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultAddInventoryMovement(\Change\Events\Event $event)
	{
		$amount = $event->getParam('amount');
		$sku = $event->getParam('sku');
		$warehouse = $event->getParam('warehouse');
		$date = $event->getParam('date');
		$target = $event->getParam('target');

		$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::addInventoryMovement');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_mvt'),
				$fb->column('sku_id'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'),
				$fb->column('target'));
			$qb->addValues(
				$fb->integerParameter('skuId'), $fb->integerParameter('amount'), $fb->integerParameter('warehouseId'),
				$fb->dateTimeParameter('dateValue'), $fb->parameter('target'));
		}
		$warehouseId = ($warehouse instanceof \Rbs\Stock\Documents\Warehouse ? $warehouse->getId() : 0);
		$dateValue = ($date instanceof \DateTime) ? $date : new \DateTime();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);

		$is = $qb->insertQuery();
		$is->bindParameter('skuId', $skuId)
			->bindParameter('amount', $amount)
			->bindParameter('warehouseId', $warehouseId)
			->bindParameter('dateValue', $dateValue)
			->bindParameter('target', $target);
		$is->execute();
		$movementId = $is->getDbProvider()->getLastInsertId('rbs_stock_dat_mvt');

		$entry = $this->getInventoryEntry($skuId, $warehouseId);
		if ($entry instanceof \Rbs\Stock\Documents\InventoryEntry)
		{
			$valueOfMovements = intval($this->getValueOfMovementsBySku($skuId, $warehouseId));
			$entry->updateValueOfMovements($valueOfMovements);
		}

		$event->setParam('movementId', $movementId);
	}

	/**
	 * @api
	 * Requires an open transaction @see \Change\Transaction\TransactionManager::begin()
	 * @param integer $movementId
	 */
	public function deleteInventoryMovementById($movementId)
	{
		if (!$this->getDisableMovement())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['movementId' => $movementId]);
			$em->trigger('deleteInventoryMovementById', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultDeleteInventoryMovementById(\Change\Events\Event $event)
	{
		$movementId = $event->getParam('movementId');
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$movementInfo = $this->getInfoByMovementId($movementId, $dbProvider);
		if (is_array($movementInfo))
		{
			$qb = $dbProvider->getNewStatementBuilder('stock::deleteInventoryMovementById');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('rbs_stock_dat_mvt'));
				$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('movementId')));
			}
			$statement = $qb->deleteQuery();
			$statement->bindParameter('movementId', $movementId);
			$statement->execute();

			list($skuId, $warehouseId) = $movementInfo;
			$entry = $this->getInventoryEntry($skuId, $warehouseId);
			if ($entry instanceof \Rbs\Stock\Documents\InventoryEntry)
			{
				$valueOfMovements = intval($this->getValueOfMovementsBySku($skuId, $warehouseId));
				$entry->updateValueOfMovements($valueOfMovements);
			}
		}
	}

	/**
	 * If return value is array index 0 is skuId and index 1 is warehouseId
	 * @param integer $movementId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return array|null
	 */
	protected function getInfoByMovementId($movementId, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder('stock::getInfoByMovementId');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('warehouse_id'), $fb->column('sku_id'));
			$qb->from($fb->table('rbs_stock_dat_mvt'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('movementId')));
		}
		$query = $qb->query();
		$query->bindParameter('movementId', $movementId);
		$result = $query->getFirstResult($query->getRowsConverter()->addIntCol('sku_id', 'warehouse_id'));
		if (is_array($result))
		{
			return [$result['sku_id'], $result['warehouse_id']];
		}
		return null;
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param integer|\Rbs\Store\Documents\WebStore $store
	 * @return integer
	 */
	public function getInventoryLevel(\Rbs\Stock\Documents\Sku $sku, $store = null)
	{
		if ($sku->getUnlimitedInventory())
		{
			return static::UNLIMITED_LEVEL;
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs(['sku' => $sku, 'store' => $store, 'level' => 0]);
		$em->trigger('getInventoryLevel', $this, $args);
		return $args['level'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryLevel(\Change\Events\Event $event)
	{
		/** @var $sku \Rbs\Stock\Documents\Sku */
		$sku = $event->getParam('sku');
		$store = $event->getParam('store');

		$warehouseId = $this->getWarehouseIdByStore($store);
		if ($this->getDisableMovement())
		{
			$movement = 0;
			$entry = $this->getInventoryEntry($sku, $warehouseId);
			$level = $entry ? $entry->getLevel() : 0;
		}
		else
		{
			$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
			$query->andPredicates($query->eq('sku', $sku), $query->eq('warehouse', $warehouseId));
			$dbQueryBuilder = $query->dbQueryBuilder();
			$fb = $dbQueryBuilder->getFragmentBuilder();

			$docTable = $query->getTableAliasName();
			$mvtTable = $fb->table('rbs_stock_dat_mvt');

			$dbQueryBuilder->leftJoin($mvtTable, $fb->logicAnd(
				$fb->eq($fb->getDocumentColumn('sku', $docTable), $fb->column('sku_id', $mvtTable)),
				$fb->eq($fb->getDocumentColumn('warehouse', $docTable), $fb->column('warehouse_id', $mvtTable))
			));
			$sum = $fb->alias($fb->sum($fb->column('movement', $mvtTable)), 'movement');
			$level = $fb->alias($fb->getDocumentColumn('level', $docTable), 'level');

			$dbQueryBuilder->addColumn($level);
			$dbQueryBuilder->addColumn($sum);

			$result = $dbQueryBuilder->query()->getFirstResult();
			$level = intval($result['level']);
			$movement = intval($result['movement']);
		}

		if (!$this->getDisableReservation())
		{
			if ($store === null)
			{
				$store = $this->getContext()->getWebStore();
			}

			if ($store)
			{
				$skuId = $sku->getId();
				$storeId = ($store instanceof \Change\Documents\AbstractDocument) ? $store->getId() : intval($store);
				$event->setParam('level', $level + $movement -
					$this->getReservedQuantity($skuId, $storeId, $event->getApplicationServices()->getDbProvider()));
				return;
			}
		}
		$event->setParam('level', $level + $movement);
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku[] $skuArray
	 * @param integer|\Rbs\Store\Documents\WebStore|null $store
	 * @return integer
	 */
	public function getInventoryLevelForManySku($skuArray, $store = null)
	{
		$skuIds = [];
		foreach ($skuArray as $sku)
		{
			if ($sku->getUnlimitedInventory())
			{
				return static::UNLIMITED_LEVEL;
			}
			$skuIds[] = $sku->getId();
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs(['skuIds' => $skuIds, 'store' => $store, 'level' => 0]);
		$em->trigger('getInventoryLevelForManySku', $this, $args);
		return $args['level'];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryLevelForManySku(\Change\Events\Event $event)
	{
		$skuIds = $event->getParam('skuIds');
		$store = $event->getParam('store');
		$warehouseId = $this->getWarehouseIdByStore($store);

		$documentQuery = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$documentQuery->andPredicates($documentQuery->in('sku', $skuIds), $documentQuery->eq('warehouse', $warehouseId));
		$dbQueryBuilder = $documentQuery->dbQueryBuilder();
		$fb = $dbQueryBuilder->getFragmentBuilder();
		$docTable = $documentQuery->getTableAliasName();
		$level = $fb->alias($fb->sum($fb->getDocumentColumn('level', $docTable)), 'level');
		$dbQueryBuilder->addColumn($level);
		$query = $dbQueryBuilder->query();
		$level = intval($query->getFirstResult($query->getRowsConverter()->addIntCol('level')->singleColumn('level')));

		if ($this->getDisableMovement())
		{
			$movement = 0;
		}
		else
		{
			$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder('onDefaultGetInventoryLevelForManySku');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->alias($fb->sum($fb->column('movement')), 'mvt'));
				$qb->from($fb->table('rbs_stock_dat_mvt'));
				$qb->where($fb->logicAnd($fb->in('sku_id', $skuIds), $fb->eq('warehouse_id', $warehouseId)));
			}
			$query = $qb->query();
			$movement = intval($query->getFirstResult($query->getRowsConverter()->addIntCol('mvt')->singleColumn('mvt')));
		}

		if (!$this->getDisableReservation())
		{
			if ($store === null)
			{
				$store = $this->getContext()->getWebStore();
			}

			if ($store)
			{
				$storeId = ($store instanceof \Change\Documents\AbstractDocument) ? $store->getId() : intval($store);
				$event->setParam('level', $level + $movement
					- $this->getReservedQuantity($skuIds, $storeId, $event->getApplicationServices()->getDbProvider()));
				return;
			}
		}
		$event->setParam('level', $level + $movement);
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param integer|\Rbs\Store\Documents\WebStore|null $store
	 * @param integer $level
	 * @return string
	 */
	public function getInventoryThreshold(\Rbs\Stock\Documents\Sku $sku, $store = null, $level = null)
	{
		if ($level === null)
		{
			$level = $this->getInventoryLevel($sku, $store);
		}
		$thresholds = $sku->getThresholds();
		if (!is_array($thresholds) || !count($thresholds))
		{
			$thresholds = $sku->getDefaultThresholds();
		}

		foreach ($thresholds as $threshold)
		{
			if ($level <= $threshold['l'])
			{
				return $threshold['c'];
			}
		}
		return $level > 0 ? static::THRESHOLD_AVAILABLE : static::THRESHOLD_UNAVAILABLE;
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku[] $skuArray
	 * @param integer|\Rbs\Store\Documents\WebStore $store
	 * @param integer $level
	 * @return string
	 */
	public function getInventoryThresholdForManySku($skuArray, $store = null, $level = null)
	{
		if ($level === null)
		{
			$level = $this->getInventoryLevelForManySku($skuArray, $store);
		}
		return $level > 0 ? static::THRESHOLD_AVAILABLE : static::THRESHOLD_UNAVAILABLE;
	}

	/**
	 * @api
	 * @param string $threshold
	 * @return string|null
	 */
	public function getInventoryThresholdTitle($threshold)
	{
		if ($threshold)
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['threshold' => $threshold, 'title' => null]);
			$em->trigger('getInventoryThresholdTitle', $this, $args);
			return $args['title'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetInventoryThresholdTitle(\Change\Events\Event $event)
	{
		$threshold = $event->getParam('threshold');
		$cm = $event->getApplicationServices()->getCollectionManager();
		$collection = $cm->getCollection('Rbs_Stock_Collection_Threshold');
		if ($collection)
		{
			$item = $collection->getItemByValue($threshold);
			if ($item)
			{
				$event->setParam('title', $item->getTitle());
			}
		}
	}

	/**
	 * @api
	 * Return not reserved quantity
	 * @param string $targetIdentifier
	 * @param \Rbs\Stock\Interfaces\Reservation[] $reservations
	 * @throws \Exception
	 * @return \Rbs\Stock\Interfaces\Reservation[]
	 */
	public function setReservations($targetIdentifier, array $reservations)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['targetIdentifier' => $targetIdentifier, 'reservations' => $reservations,
				'unReservable' => []]);
			$em->trigger('setReservations', $this, $args);
			if (is_array($args['unReservable']))
			{
				return $args['unReservable'];
			}
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultSetReservations(\Change\Events\Event $event)
	{
		$targetIdentifier = $event->getParam('targetIdentifier');
		/** @var $reservations \Rbs\Stock\Interfaces\Reservation[] */
		$reservations = $event->getParam('reservations');
		$date = new \DateTime();
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();
		$unReservable = [];
		try
		{
			$transactionManager->begin();

			/* @var $currentReservations \Rbs\Stock\Std\Reservation[] */
			$currentReservations = $this->getReservationsByTarget($targetIdentifier);

			foreach ($reservations as $reservation)
			{
				$sku = $this->getSkuByCode($reservation->getCodeSku());
				if (!$sku)
				{
					$unReservable[] = $reservation;
					continue;
				}

				$currentReservation = array_reduce($currentReservations,
					function ($result, \Rbs\Stock\Std\Reservation $res) use ($reservation)
					{
						return $res->isSame($reservation) ? $res : $result;
					});

				if (!$sku->getUnlimitedInventory() && !$sku->getAllowBackorders())
				{
					$level = $this->getInventoryLevel($sku, $reservation->getWebStoreId());
					if ($currentReservation instanceof \Rbs\Stock\Std\Reservation)
					{
						$level += $currentReservation->getQuantity();
					}
					if ($level < $reservation->getQuantity())
					{
						$unReservable[] = $reservation;
						if ($reservation instanceof \Rbs\Commerce\Cart\CartReservation)
						{
							$reservation->setQuantityNotReserved($reservation->getQuantity() - $level);
						}
						continue;
					}
				}

				if ($currentReservation instanceof \Rbs\Stock\Std\Reservation)
				{
					$currentReservation->setQuantity($reservation->getQuantity());
					$this->updateReservation($currentReservation, $date, $dbProvider);
				}
				else
				{
					$currentReservation = (new \Rbs\Stock\Std\Reservation())->fromReservation($reservation);
					$currentReservation->setSkuId($sku->getId());
					$this->insertReservation($targetIdentifier, $currentReservation, $date, $dbProvider);
				}
			}

			foreach ($currentReservations as $currentReservation)
			{
				$res = array_reduce($reservations, function (\Rbs\Stock\Std\Reservation $result = null, $res)
				{
					return ($result && $result->isSame($res)) ? null : $result;
				}, $currentReservation);

				if ($res instanceof \Rbs\Stock\Std\Reservation)
				{
					$this->deleteReservationById($res->getId(), $dbProvider);
				}
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
		$event->setParam('unReservable', $unReservable);
	}

	/**
	 * @param integer | integer[] $skuId
	 * @param integer $storeId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return integer
	 */
	protected function getReservedQuantity($skuId, $storeId, $dbProvider)
	{
		if (is_array($skuId))
		{
			if (count($skuId) > 1)
			{
				return $this->getReservedQuantityByArray($skuId, $storeId, $dbProvider);
			}
			$skuId = $skuId[0];
		}

		$qb = $dbProvider->getNewQueryBuilder('stock::getReservedQuantity');
		if (!$qb->isCached())
		{

			$fb = $qb->getFragmentBuilder();
			$resTable = $fb->table('rbs_stock_dat_res');
			$qb->select($fb->alias($fb->sum($fb->column('reservation')), 'quantity'));
			$qb->from($resTable);

			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')),
					$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId'))
				)
			);
		}
		$query = $qb->query();
		$query->bindParameter('skuId', $skuId);
		$query->bindParameter('storeId', $storeId);
		return intval($query->getFirstResult($query->getRowsConverter()->addIntCol('quantity')));
	}

	/**
	 * @param integer[] $skuIds
	 * @param integer $storeId
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return integer
	 */
	protected function getReservedQuantityByArray($skuIds, $storeId, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder('stock::getReservedQuantityByArray');

		$fb = $qb->getFragmentBuilder();
		$resTable = $fb->table('rbs_stock_dat_res');
		$qb->select($fb->alias($fb->sum($fb->column('reservation')), 'quantity'));
		$qb->from($resTable);

		$qb->where(
			$fb->logicAnd(
				$fb->in($fb->column('sku_id'), $skuIds),
				$fb->eq($fb->column('store_id'), $fb->integerParameter('storeId'))
			)
		);

		$query = $qb->query();
		$query->bindParameter('storeId', $storeId);
		return intval($query->getFirstResult($query->getRowsConverter()->addIntCol('quantity')));
	}

	/**
	 * @api
	 * @param @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @return array|mixed|null
	 */
	public function getReservationsInfosBySkuGroupByStoreAndStatus($sku)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'reservations' => []]);
			$em->trigger('getReservationsInfosBySkuGroupByStoreAndStatus', $this, $args);
			if (is_array($args['reservations']))
			{
				return $args['reservations'];
			}
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetReservationsInfosBySkuGroupByStoreAndStatus(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$qb =
			$event->getApplicationServices()->getDbProvider()->getNewQueryBuilder('stock::getReservationsInfosBySkuGroupByStore');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('store_id'), $fb->column('confirmed'), $fb->alias($fb->func('count', '*'), 'count'),
				$fb->alias($fb->sum($fb->column('reservation')), 'reservation'));
			$qb->from($fb->table('rbs_stock_dat_res'));

			$qb->where(
				$fb->logicAnd(
					$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
				)
			);

			$qb->group($fb->column('store_id'));
			$qb->group($fb->column('confirmed'));
		}
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		$reservations = $query->getResults($query->getRowsConverter()->addIntCol('store_id', 'count', 'reservation')
			->addBoolCol('confirmed'));
		$event->setParam('reservations', $reservations);
	}

	/**
	 * @param string $targetIdentifier
	 * @param \Rbs\Stock\Std\Reservation $reservation
	 * @param \DateTime $date
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function insertReservation($targetIdentifier, \Rbs\Stock\Std\Reservation $reservation, \DateTime $date, $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('stock::insertReservation');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_res'),
				$fb->column('sku_id'), $fb->column('reservation'),
				$fb->column('store_id'), $fb->column('target'), $fb->column('date'));
			$qb->addValues($fb->integerParameter('skuId'), $fb->integerParameter('reservation'),
				$fb->integerParameter('storeId'), $fb->parameter('target'), $fb->dateTimeParameter('date')
			);
		}
		$statement = $qb->insertQuery();
		$statement->bindParameter('skuId', $reservation->getSkuId());
		$statement->bindParameter('reservation', $reservation->getQuantity());
		$statement->bindParameter('storeId', $reservation->getWebStoreId());
		$statement->bindParameter('target', $targetIdentifier);
		$statement->bindParameter('date', $date);
		$statement->execute();
		$reservation->setId($statement->getDbProvider()->getLastInsertId('rbs_stock_dat_res'));
	}

	/**
	 * @param \Rbs\Stock\Std\Reservation $reservation
	 * @param \DateTime $date
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function updateReservation(\Rbs\Stock\Std\Reservation $reservation, \DateTime $date, $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('stock::updateReservation');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->update($fb->table('rbs_stock_dat_res'));
			$qb->assign($fb->column('reservation'), $fb->integerParameter('reservation'));
			$qb->assign($fb->column('date'), $fb->dateTimeParameter('date'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('reservationId')));
		}
		$statement = $qb->updateQuery();
		$statement->bindParameter('reservation', $reservation->getQuantity());
		$statement->bindParameter('date', $date);
		$statement->bindParameter('reservationId', $reservation->getId());
		$statement->execute();
	}

	/**
	 * @param integer $reservationId
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function deleteReservationById($reservationId, $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder('stock::deleteReservationById');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->delete($fb->table('rbs_stock_dat_res'));
			$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('reservationId')));
		}
		$statement = $qb->deleteQuery();
		$statement->bindParameter('reservationId', $reservationId);
		$statement->execute();
	}

	/**
	 * @api
	 * @param string $targetIdentifier
	 * @return \Rbs\Stock\Interfaces\Reservation[]
	 */
	public function getReservationsByTarget($targetIdentifier)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['targetIdentifier' => $targetIdentifier, 'reservations' => []]);
			$em->trigger('getReservationsByTarget', $this, $args);
			if (is_array($args['reservations']))
			{
				return $args['reservations'];
			}
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReservationsByTarget(\Change\Events\Event $event)
	{
		$targetIdentifier = $event->getParam('targetIdentifier');

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder('stock::getReservations');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$skuTable = $fb->getDocumentTable('Rbs_Stock_Sku');
			$resTable = $fb->table('rbs_stock_dat_res');
			$qb->select($fb->column('id', $resTable), $fb->column('sku_id', $resTable),
				$fb->alias($fb->getDocumentColumn('code', $skuTable), 'sku_code'),
				$fb->column('reservation', $resTable), $fb->column('store_id', $resTable));
			$qb->from($resTable)
				->innerJoin($skuTable, $fb->eq($fb->column('sku_id', $resTable), $fb->getDocumentColumn('id', $skuTable)));
			$qb->where($fb->eq($fb->column('target', $resTable), $fb->parameter('targetIdentifier')));
		}
		$query = $qb->query();
		$query->bindParameter('targetIdentifier', $targetIdentifier);
		$rows = $query->getResults($query->getRowsConverter()->addIntCol('id', 'store_id', 'sku_id')
			->addNumCol('reservation')->addStrCol('sku_code'));

		if (count($rows))
		{
			$reservations = array_map(function (array $row)
			{
				return (new \Rbs\Stock\Std\Reservation($row['id']))
					->setCodeSku($row['sku_code'])
					->setQuantity($row['reservation'])
					->setSkuId($row['sku_id'])
					->setWebStoreId($row['store_id']);
			}, $rows);
			$event->setParam('reservations', $reservations);
		}
	}

	/**
	 * Remove not confirmed reservation for target identifier
	 * @api
	 * @param string $targetIdentifier
	 * @throws \Exception
	 */
	public function cleanupReservations($targetIdentifier)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['targetIdentifier' => $targetIdentifier]);
			$em->trigger('cleanupReservations', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultCleanupReservations(\Change\Events\Event $event)
	{
		$targetIdentifier = $event->getParam('targetIdentifier');
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::cleanupReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('rbs_stock_dat_res'));
				$qb->where(
					$fb->logicAnd(
						$fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')),
						$fb->eq($fb->column('confirmed'), $fb->booleanParameter('confirmed'))
					)
				);
			}
			$statement = $qb->deleteQuery();
			$statement->bindParameter('targetIdentifier', $targetIdentifier);
			$statement->bindParameter('confirmed', false);
			$statement->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * Remove all reservation for target identifier
	 * @api
	 * @param string $targetIdentifier
	 * @throws \Exception
	 */
	public function unsetReservations($targetIdentifier)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['targetIdentifier' => $targetIdentifier]);
			$em->trigger('unsetReservations', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultUnsetReservations(\Change\Events\Event $event)
	{
		$targetIdentifier = $event->getParam('targetIdentifier');
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::unsetReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->delete($fb->table('rbs_stock_dat_res'));
				$qb->where($fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')));
			}
			$statement = $qb->deleteQuery();
			$statement->bindParameter('targetIdentifier', $targetIdentifier);
			$statement->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @api
	 * @param string $targetIdentifier
	 * @param string $confirmedTargetIdentifier
	 * @throws \Exception
	 */
	public function confirmReservations($targetIdentifier, $confirmedTargetIdentifier = null)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['targetIdentifier' => $targetIdentifier,
				'confirmedTargetIdentifier' => $confirmedTargetIdentifier]);
			$em->trigger('confirmReservations', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultConfirmReservations(\Change\Events\Event $event)
	{
		$targetIdentifier = $event->getParam('targetIdentifier');
		$confirmedTargetIdentifier = $event->getParam('confirmedTargetIdentifier');
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::confirmReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_stock_dat_res'));
				$qb->assign($fb->column('confirmed'), $fb->booleanParameter('confirmed'));
				$qb->assign($fb->column('target'), $fb->parameter('confirmedTargetIdentifier'));
				$qb->where($fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')));
			}
			$statement = $qb->updateQuery();
			$statement->bindParameter('confirmed', true);
			$statement->bindParameter('confirmedTargetIdentifier',
				$confirmedTargetIdentifier ? $confirmedTargetIdentifier : $targetIdentifier);
			$statement->bindParameter('targetIdentifier', $targetIdentifier);
			$statement->execute();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @api
	 * @param string $fromTargetIdentifier
	 * @param string $toTargetIdentifier
	 * @throws \Exception
	 */
	public function transferReservations($fromTargetIdentifier, $toTargetIdentifier)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['fromTargetIdentifier' => $fromTargetIdentifier,
				'toTargetIdentifier' => $toTargetIdentifier]);
			$em->trigger('transferReservations', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultTransferReservations(\Change\Events\Event $event)
	{
		$fromTargetIdentifier = $event->getParam('fromTargetIdentifier');
		$toTargetIdentifier = $event->getParam('toTargetIdentifier');
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $event->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::transferReservations');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_stock_dat_res'));
				$qb->assign($fb->column('target'), $fb->parameter('toTargetIdentifier'));
				$qb->where($fb->eq($fb->column('target'), $fb->parameter('fromTargetIdentifier')));
			}
			$statement = $qb->updateQuery();
			$statement->bindParameter('toTargetIdentifier', $toTargetIdentifier);
			$statement->bindParameter('fromTargetIdentifier', $fromTargetIdentifier);
			$statement->execute();
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @api
	 * @param $targetIdentifier
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param $quantity
	 * @throws \Exception
	 */
	public function decrementReservation($targetIdentifier, $sku, $quantity)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['targetIdentifier' => $targetIdentifier,
				'sku' => $sku, 'quantity' => $quantity]);
			$em->trigger('decrementReservation', $this, $args);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultDecrementReservation(\Change\Events\Event $event)
	{
		$targetIdentifier = $event->getParam('targetIdentifier');
		$sku = $event->getParam('sku');
		$quantity = $event->getParam('quantity');
		$appSrv = $event->getApplicationServices();
		$transactionManager = $appSrv->getTransactionManager();
		$dbProvider = $appSrv->getDbProvider();
		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : $sku;
		try
		{
			$transactionManager->begin();

			//get the current reservation
			$qb = $dbProvider->getNewQueryBuilder('stock::decrementReservationSelect');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('id'), $fb->column('sku_id'), $fb->column('reservation'));
				$qb->from($fb->table('rbs_stock_dat_res'));
				$qb->where($fb->logicAnd(
					$fb->eq($fb->column('target'), $fb->parameter('targetIdentifier')),
					$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId')))
				);
			}
			$query = $qb->query();
			$query->bindParameter('targetIdentifier', $targetIdentifier);
			$query->bindParameter('skuId', $skuId);

			$row = $query->getFirstResult($query->getRowsConverter()->addIntCol('id', 'sku_id')
				->addNumCol('reservation'));

			//if we found a reservation, we'll decrement his quantity
			if ($row)
			{
				$newReservation = $row['reservation'] - $quantity;
				//we cannot have a reservation under 0.
				if ($newReservation < 0)
				{
					$newReservation = 0;
				}

				$qb = $dbProvider->getNewStatementBuilder('stock::decrementReservationUpdate');
				if (!$qb->isCached())
				{
					$fb = $qb->getFragmentBuilder();
					$qb->update($fb->table('rbs_stock_dat_res'));
					$qb->assign($fb->column('reservation'), $fb->integerParameter('reservation'));
					$qb->assign($fb->column('date'), $fb->dateTimeParameter('date'));
					$qb->where($fb->eq($fb->column('id'), $fb->integerParameter('reservationId')));
				}
				$statement = $qb->updateQuery();
				$statement->bindParameter('reservation', $newReservation);
				$statement->bindParameter('date', new \DateTime());
				$statement->bindParameter('reservationId', $row['id']);
				$statement->execute();
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $store
	 * @param integer|null $limit
	 * @param integer|null $offset
	 * @param string|null $orderCol
	 * @param string|null $orderSort
	 * @return array
	 */
	public function getReservationsBySku($sku, $store = null, $limit = null, $offset = null, $orderCol = null, $orderSort = null)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'store' => $store,
				'limit' => $limit, 'offset' => $offset, 'orderCol' => $orderCol, 'orderSort' => $orderSort,
				'reservations' => []]);
			$em->trigger('getReservationsBySku', $this, $args);
			if (is_array($args['reservations']))
			{
				return $args['reservations'];
			}
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetReservationsBySku(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$store = $event->getParam('store');
		$limit = $event->getParam('limit');
		$offset = $event->getParam('offset');
		$orderCol = $event->getParam('orderCol');
		$orderSort = $event->getParam('orderSort');

		$appSrv = $event->getApplicationServices();
		$dbProvider = $appSrv->getDbProvider();

		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('reservation'), $fb->column('store_id'), $fb->column('date'), $fb->column('target'),
			$fb->column('confirmed')); //
		$qb->from($fb->table('rbs_stock_dat_res'));
		$logicAnd = $fb->logicAnd(
			$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
		);
		if ($store !== null)
		{
			$logicAnd->addArgument($fb->eq($fb->column('store_id'), $fb->integerParameter('storeId')));
		}
		$qb->where($logicAnd);

		if ($orderSort !== null && $orderCol !== null)
		{
			if ($orderSort === 'desc')
			{
				$qb->orderDesc($fb->column($orderCol));
			}
			else
			{
				$qb->orderAsc($fb->column($orderCol));
			}
		}

		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($store !== null)
		{
			$storeId = $store instanceof \Rbs\Store\Documents\WebStore ? $store->getId() : intval($store);
			$query->bindParameter('warehouseId', $storeId);
		}

		if ($limit !== null && $offset !== null)
		{
			$query->setMaxResults($limit);
			$query->setStartIndex($offset);
		}

		$queryResult = $query->getResults();
		$event->setParam('reservations', $queryResult);
	}

	/**
	 * @api
	 * @param \Rbs\Stock\Documents\Sku|integer $sku
	 * @param \Rbs\Store\Documents\WebStore|integer $store
	 * @return integer
	 */
	public function countReservationsBySku($sku, $store = null)
	{
		if (!$this->getDisableReservation())
		{
			$em = $this->getEventManager();
			$args = $em->prepareArgs(['sku' => $sku, 'store' => $store,
				'count' => 0]);
			$em->trigger('countReservationsBySku', $this, $args);
			return $args['count'];
		}
		return 0;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultCountReservationsBySku(\Change\Events\Event $event)
	{
		$sku = $event->getParam('sku');
		$store = $event->getParam('store');

		$key = 'stock::countReservationsBySku';
		if ($store !== null)
		{
			$key = 'stock::countReservationsBySkuAndStore';
		}

		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder($key);
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->alias($fb->func('count', '*'), 'rowCount'));
			$qb->from($fb->table('rbs_stock_dat_res'));
			$logicAnd = $fb->logicAnd(
				$fb->eq($fb->column('sku_id'), $fb->integerParameter('skuId'))
			);
			if ($store !== null)
			{
				$logicAnd->addArgument($fb->eq($fb->column('store_id'), $fb->integerParameter('storeId')));
			}
			$qb->where($logicAnd);
		}
		$query = $qb->query();

		$skuId = $sku instanceof \Rbs\Stock\Documents\Sku ? $sku->getId() : intval($sku);
		$query->bindParameter('skuId', $skuId);

		if ($store !== null)
		{
			$storeId = $store instanceof \Rbs\Store\Documents\WebStore ? $store->getId() : intval($store);
			$query->bindParameter('warehouseId', $storeId);
		}

		$count = $query->getFirstResult($query->getRowsConverter()->addIntCol('rowCount')->singleColumn('rowCount'));
		$event->setParam('count', $count);
	}

	/**
	 * @api
	 * @param string $code
	 * @return \Rbs\Stock\Documents\Sku|null
	 */
	public function getSkuByCode($code)
	{
		if (!is_string($code))
		{
			return null;
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs(['code' => $code, 'sku' => null]);
		$em->trigger('getSkuByCode', $this, $args);
		return $args['sku'];
	}

	protected $skuIds = [];

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetSkuByCode(\Change\Events\Event $event)
	{
		$code = $event->getParam('code');

		if (array_key_exists($code, $this->skuIds))
		{
			$skuId = $this->skuIds[$code];
			if (is_int($skuId))
			{
				$sku = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($skuId);
				$event->setParam('sku', $sku);
			}
			return;
		}

		$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Stock_Sku');
		$query->andPredicates($query->eq('code', $code));
		$sku = $query->getFirstDocument();
		if ($sku)
		{
			$this->skuIds[$code] = $sku->getId();
			$event->setParam('sku', $sku);
		}
	}

	/**
	 * @api
	 * @param string $targetIdentifier
	 * @return integer|null
	 */
	public function getTargetIdFromTargetIdentifier($targetIdentifier)
	{
		$split = explode(':', $targetIdentifier);

		if (count($split) == 2 && is_numeric($split[1]))
		{
			return $split[1];
		}
		return null;
	}

	/**
	 * @api
	 * @param \Rbs\Catalog\Documents\Product|integer $product
	 * @param \Rbs\Stock\Documents\Warehouse|integer $warehouse
	 * @return bool
	 */
	public function getProductAvailability($product, $warehouse = null)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['product' => $product, 'warehouse' => $warehouse,
			'availability' => false]);
		$em->trigger('getProductAvailability', $this, $args);
		return ($args['availability'] == true);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetProductAvailability(\Change\Events\Event $event)
	{
		$product = $event->getParam('product');
		$warehouse = $event->getParam('warehouse');
		$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder('getProductAvailability');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$tableIdt = $fb->identifier('rbs_stock_dat_availability');
			$qb->select($fb->column('availability'))
				->from($fb->table('rbs_stock_dat_availability'))
				->where(
					$fb->logicAnd(
						$fb->gt($fb->column('availability', $tableIdt), $fb->number(0)),
						$fb->eq($fb->column('product_id', $tableIdt), $fb->integerParameter('productId')),
						$fb->eq($fb->column('warehouse_id', $tableIdt), $fb->integerParameter('warehouseId'))
					)
				);
		}
		$sq = $qb->query();
		$productId = ($product instanceof \Rbs\Catalog\Documents\Product) ? $product->getId() : intval($product);
		$sq->bindParameter('productId', $productId);
		$warehouseId = ($warehouse instanceof \Rbs\Stock\Documents\Warehouse) ? $warehouse->getId() : intval($warehouse);
		$sq->bindParameter('warehouseId', $warehouseId);

		$availability = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('availability')->singleColumn('availability'));

		$event->setParam('availability', $availability ? true : false);
	}

	/**
	 * @api
	 * @param \Change\Db\DbProvider $dDbProvider
	 * @param \Change\Db\Query\Expressions\AbstractExpression $productSQLFragment
	 * @param integer $warehouseId
	 * @return \Change\Db\Query\Predicates\Exists
	 */
	public function getProductAvailabilityRestriction($dDbProvider, $productSQLFragment, $warehouseId = 0)
	{
		$qb = $dDbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$tableIdt = $fb->identifier('rbs_stock_dat_availability');
		$qb->select($fb->allColumns())
			->from($fb->table('rbs_stock_dat_availability'))
			->where(
				$fb->logicAnd(
					$fb->gt($fb->column('availability', $tableIdt), $fb->number(0)),
					$fb->eq($fb->column('warehouse_id', $tableIdt), $fb->number(intval($warehouseId))),
					$fb->eq($fb->column('product_id', $tableIdt), $productSQLFragment)
				)
			);
		return $fb->exists($qb->query());
	}
}