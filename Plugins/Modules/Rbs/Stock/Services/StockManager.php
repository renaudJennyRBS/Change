<?php
namespace Rbs\Stock\Services;

use Rbs\Commerce\Services\CommerceServices;
use Rbs\Stock\Setup\Schema;

/**
 * @name \Rbs\Stock\Services\StockManager
 */
class StockManager
{
	const INVENTORY_UNIT_PIECE = 0;

	/**
	 * @var CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices(CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
		return $this;
	}

	/**
	 * @return CommerceServices
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
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @return \Rbs\Stock\Documents\InventoryEntry|null
	 */
	public function getInventoryEntry($sku, $warehouse = null)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
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
			$entry = $this->getDocumentServices()->getDocumentManager()
				->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		}
		$tm = $this->getApplicationServices()->getTransactionManager();
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('addInventoryIssueReceipt');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$qb->insert($fb->table('rbs_stock_dat_mvt'),
				$fb->column('sku_id'), $fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'));
			$qb->addValues(
				$fb->integerParameter('skuId'), $fb->integerParameter('amount'), $fb->integerParameter('warehouseId'), $fb->dateTimeParameter('dateValue'));
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
	 * @param $store
	 * @return integer
	 */
	public function getInventoryLevel($sku, $store)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->andPredicates($query->eq('sku', $sku), $query->eq('warehouse', 0));
		$dbQueryBuilder = $query->dbQueryBuilder();
		$fb = $dbQueryBuilder->getFragmentBuilder();

		$docTable = $query->getTableAliasName();
		$mvtTable = $fb->table('rbs_stock_dat_mvt');

		$dbQueryBuilder->innerJoin($mvtTable, $fb->logicAnd(
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

		if ($store)
		{
			$skuId = ($sku instanceof \Change\Documents\AbstractDocument) ? $sku->getId() : intval($sku);
			$storeId = ($store instanceof \Change\Documents\AbstractDocument)? $store->getId() : intval($store);
			return $level + $movement - $this->getReservedQuantity($skuId, $storeId);
		}

		return $level + $movement;
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
		$transactionManager = $this->getApplicationServices()->getTransactionManager();
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

				$currentReservation = array_reduce($currentReservations, function($result, \Rbs\Stock\Std\Reservation $res) use ($reservation) {
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
				$res = array_reduce($reservations, function(\Rbs\Stock\Std\Reservation $result = null, $res) {
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder('stock::getReservedQuantity');
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::insertReservation');
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::updateReservation');
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::deleteReservationById');
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
		$qb = $this->getApplicationServices()->getDbProvider()->getNewQueryBuilder('stock::getReservations');
		if (!$qb->isCached())
		{
			$fb = $qb->getFragmentBuilder();
			$skuTable = $fb->getDocumentTable('Rbs_Stock_Sku');
			$resTable = $fb->table('rbs_stock_dat_res');
			$qb->select($fb->column('id', $resTable), $fb->column('sku_id', $resTable),$fb->alias($fb->getDocumentColumn('code', $skuTable), 'sku_code'),
				$fb->column('reservation', $resTable), $fb->column('store_id', $resTable));
			$qb->from($resTable)->innerJoin($skuTable, $fb->eq($fb->column('sku_id', $resTable), $fb->getDocumentColumn('id', $skuTable)));
			$qb->where($fb->eq($fb->column('target', $resTable), $fb->parameter('targetIdentifier')));
		}
		$query = $qb->query();
		$query->bindParameter('targetIdentifier', $targetIdentifier);
		$rows = $query->getResults($query->getRowsConverter()->addIntCol('id', 'store_id', 'sku_id')
			->addNumCol('reservation')->addStrCol('sku_code'));
		if (count($rows))
		{
			return array_map(function(array $row) {
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
		$transactionManager = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$qb = $this->getApplicationServices()->getDbProvider()->getNewStatementBuilder('stock::unsetReservations');
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
				return $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($skuId);
			}
			return null;
		}

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_Sku');
		$query->andPredicates($query->eq('code', $code));
		$sku = $query->getFirstDocument();
		$this->skuIds[$code] = ($sku) ? $sku->getId() : null;
		return $sku;
	}
}