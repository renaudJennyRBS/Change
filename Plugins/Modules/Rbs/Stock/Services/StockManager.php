<?php
namespace Rbs\Stock\Services;

/**
 * @name \Rbs\Stock\Services\StockManager
 */
class StockManager
{
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
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Db\DbProvider
	 */
	protected $dbProvider;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

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
	 * @param \Change\Collection\CollectionManager $collectionManager
	 * @return $this
	 */
	public function setCollectionManager($collectionManager)
	{
		$this->collectionManager = $collectionManager;
		return $this;
	}

	/**
	 * @return \Change\Collection\CollectionManager
	 */
	protected function getCollectionManager()
	{
		return $this->collectionManager;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @return \Rbs\Stock\Documents\InventoryEntry|null
	 */
	public function getInventoryEntry($sku, $warehouse = null)
	{
		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates(
			$query->eq('sku', $sku),
			$query->eq('warehouse', $warehouse)
		);
		return $query->getFirstDocument();
	}

	/**
	 * @param integer $level
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @throws \Exception
	 * @return \Rbs\Stock\Documents\InventoryEntry
	 */
	public function setInventory($level, $sku, $warehouse = null)
	{
		$entry = $this->getInventoryEntry($sku, $warehouse);
		if ($entry === null)
		{
			/* @var $entry \Rbs\Stock\Documents\InventoryEntry */
			$entry = $this->getDocumentManager()
				->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		}
		$tm = $this->getTransactionManager();
		try
		{
			$tm->begin();
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
		return $entry;
	}

	/**
	 * Positive = receipt, negative = issue
	 * @param integer $amount
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @param \DateTime|null $date
	 * @throws \Exception
	 * @return integer
	 */
	public function addInventoryMovement($amount, \Rbs\Stock\Documents\Sku $sku, $warehouse = null, $date = null)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('addInventoryIssueReceipt');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_mvt'),
				$fb->column('sku_id'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'));
			$qb->addValues(
				$fb->integerParameter('skuId'), $fb->integerParameter('amount'), $fb->integerParameter('warehouseId'),
				$fb->dateTimeParameter('dateValue'));
		}
		$warehouseId = ($warehouse instanceof \Rbs\Stock\Documents\AbstractWarehouse ? $warehouse->getId() : 0);
		$dateValue = ($date instanceof \DateTime) ? $date : new \DateTime();

		$is = $qb->insertQuery();
		$is->bindParameter('skuId', $sku->getId())
			->bindParameter('amount', $amount)
			->bindParameter('warehouseId', $warehouseId)
			->bindParameter('dateValue', $dateValue);
		$is->execute();
		return $is->getDbProvider()->getLastInsertId('rbs_catalog_dat_attribute');
	}

	/**
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

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->eq('sku', $sku), $query->eq('warehouse', 0));
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

		if ($store === null)
		{
			$store = $this->getContext()->getWebStore();
		}

		if ($store)
		{
			$skuId = $sku->getId();
			$storeId = ($store instanceof \Change\Documents\AbstractDocument) ? $store->getId() : intval($store);
			return $level + $movement - $this->getReservedQuantity($skuId, $storeId);
		}
		return $level + $movement;
	}

	/**
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param integer|\Rbs\Store\Documents\WebStore $store
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
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param integer|\Rbs\Store\Documents\WebStore $store
	 * @param string $threshold
	 * @return string|null
	 */
	public function getInventoryThresholdTitle(\Rbs\Stock\Documents\Sku $sku, $store = null, $threshold = null)
	{
		if ($threshold === null)
		{
			$threshold = $this->getInventoryThreshold($sku, $store);
		}
		if ($threshold)
		{
			$cm = $this->getCollectionManager();
			$collection = $cm->getCollection('Rbs_Stock_Collection_Threshold');
			if ($collection)
			{
				$item = $collection->getItemByValue($threshold);
				if ($item)
				{
					return $item->getTitle();
				}
			}
		}
		return null;
	}

	/**
	 * Return not reserved quantity
	 * @param string $targetIdentifier
	 * @param \Rbs\Stock\Interfaces\Reservation[] $reservations
	 * @throws \Exception
	 * @return \Rbs\Stock\Interfaces\Reservation[]
	 */
	public function setReservations($targetIdentifier, array $reservations)
	{
		$date = new \DateTime();
		$transactionManager = $this->getTransactionManager();
		$result = array();
		try
		{
			$transactionManager->begin();

			/* @var $currentReservations \Rbs\Stock\Std\Reservation[] */
			$currentReservations = $this->getReservations($targetIdentifier);

			foreach ($reservations as $reservation)
			{
				$sku = $this->getSkuByCode($reservation->getCodeSku());
				if (!$sku)
				{
					$result[] = $reservation;
					continue;
				}

				$currentReservation = array_reduce($currentReservations,
					function ($result, \Rbs\Stock\Std\Reservation $res) use ($reservation)
					{
						return $res->isSame($reservation) ? $res : $result;
					});

				if ($currentReservation instanceof \Rbs\Stock\Std\Reservation)
				{
					$currentReservation->setQuantity($reservation->getQuantity());
					$this->updateReservation($currentReservation, $date);
				}
				else
				{
					$currentReservation = (new \Rbs\Stock\Std\Reservation())->fromReservation($reservation);
					$currentReservation->setSkuId($sku->getId());
					$this->insertReservation($targetIdentifier, $currentReservation, $date);
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
					$this->deleteReservationById($res->getId());
				}
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
		return $result;
	}

	/**
	 * @param integer $skuId
	 * @param integer $storeId
	 * @return integer
	 */
	protected function getReservedQuantity($skuId, $storeId)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('stock::getReservedQuantity');
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
	 * @param string $targetIdentifier
	 * @param \Rbs\Stock\Std\Reservation $reservation
	 * @param \DateTime $date
	 */
	protected function insertReservation($targetIdentifier, \Rbs\Stock\Std\Reservation $reservation, \DateTime $date)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::insertReservation');
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
	 */
	protected function updateReservation(\Rbs\Stock\Std\Reservation $reservation, \DateTime $date)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::updateReservation');
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
	 */
	protected function deleteReservationById($reservationId)
	{
		$qb = $this->getDbProvider()->getNewStatementBuilder('stock::deleteReservationById');
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
	 * @param string $targetIdentifier
	 * @return \Rbs\Stock\Interfaces\Reservation[]
	 */
	public function getReservations($targetIdentifier)
	{
		$qb = $this->getDbProvider()->getNewQueryBuilder('stock::getReservations');
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
			return array_map(function (array $row)
			{
				return (new \Rbs\Stock\Std\Reservation($row['id']))
					->setCodeSku($row['sku_code'])
					->setQuantity($row['reservation'])
					->setSkuId($row['sku_id'])
					->setWebStoreId($row['store_id']);
			}, $rows);
		}
		return array();
	}

	/**
	 * Return not reserved quantity
	 * @param string $targetIdentifier
	 * @throws \Exception
	 */
	public function unsetReservations($targetIdentifier)
	{
		$transactionManager = $this->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $this->getDbProvider()->getNewStatementBuilder('stock::unsetReservations');
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

	protected $skuIds = array();

	/**
	 * @param string $code
	 * @return \Rbs\Stock\Documents\Sku|null
	 */
	public function getSkuByCode($code)
	{
		if (!is_string($code))
		{
			return null;
		}
		if (array_key_exists($code, $this->skuIds))
		{
			$skuId = $this->skuIds[$code];
			if (is_int($skuId))
			{
				return $this->getDocumentManager()->getDocumentInstance($skuId);
			}
			return null;
		}

		$query = $this->getDocumentManager()->getNewQuery('Rbs_Stock_Sku');
		$query->andPredicates($query->eq('code', $code));
		$sku = $query->getFirstDocument();
		if ($sku)
		{
			$this->skuIds[$code] =  $sku->getId();
		}
		return $sku;
	}
}