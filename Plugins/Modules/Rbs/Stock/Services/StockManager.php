<?php
namespace Rbs\Stock\Services;

use Rbs\Stock\Setup\Schema;

/**
 * @name \Rbs\Stock\Services\StockManager
 */
class StockManager
{
	const INVENTORY_UNIT_PIECE = 0;

	/**
	 * @var \Rbs\Commerce\Services\CommerceServices
	 */
	protected $commerceServices;

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @return $this
	 */
	public function setCommerceServices(\Rbs\Commerce\Services\CommerceServices $commerceServices)
	{
		$this->commerceServices = $commerceServices;
		return $this;
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
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @param int $unit
	 * @param \DateTime|null $date
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
	 * @param int $level
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @param int $unit
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
	 * @param int $amount
	 * @param \Rbs\Stock\Documents\Sku $sku
	 * @param \Rbs\Stock\Documents\AbstractWarehouse|null $warehouse
	 * @param int $unit
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
			$qb->insert($fb->table(Schema::MVT_TABLE),
				$fb->column('sku_id'), $fb->column('unit'),
				$fb->column('movement'), $fb->column('warehouse_id'), $fb->column('date'));
			$qb->addValues(
				$fb->integerParameter('skuId'),
				$fb->integerParameter('amount'), $fb->decimalParameter('warehouseId'), $fb->dateTimeParameter('dateValue'));
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
	 * @param $sku
	 * @param $store
	 */
	public function getInventoryLevel($sku, $store)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->andPredicates(
			$query->eq('sku', $sku),
			$query->eq('warehouse', 0)
		);
		$llqb = $query->dbQueryBuilder();
		$fb = $llqb->getFragmentBuilder();

		$docTable = $query->getTableAliasName();
		$mvtTable = $fb->table(Schema::MVT_TABLE);
		$llqb->innerJoin($mvtTable, $fb->logicAnd(
			$fb->eq($fb->getDocumentColumn('sku', $docTable), $fb->column('sku_id', $mvtTable)),
			$fb->eq($fb->getDocumentColumn('warehouse', $docTable), $fb->column('warehouse_id', $mvtTable))
		));
		$sum = $fb->alias($fb->sum($fb->column('movement', $mvtTable)), 'movement');
		$level = $fb->alias($fb->getDocumentColumn('level', $docTable), 'level');

		$llqb->addColumn($level);
		$llqb->addColumn($sum);

		$result = $llqb->query()->getFirstResult();
		$level = intval($result['level']);
		$movement = intval($result['movement']);
		return $level - $movement;
	}

	/**
	 * @param $sku
	 * @param $store
	 */
	public function getReservationLevel($sku, $store)
	{

	}

	/**
	 * @return \Rbs\Stock\Documents\Sku
	 */
	public function getSkuByCode($code)
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_Sku');
		$query->andPredicates($query->eq('code', $code));
		return $query->getFirstDocument();
	}
}