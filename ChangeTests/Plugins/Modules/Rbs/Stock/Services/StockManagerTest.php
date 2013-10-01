<?php

namespace ChangeTests\Plugins\Modules\Stock\Services;
use Rbs\Stock\Services\StockManager;

class StockManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	/**
	 * @var \Rbs\Stock\Services\StockManager
	 */
	protected $sm;

	public static function setUpBeforeClass()
	{
		$appServices = static::initDocumentsDb();
		$schema = new \Rbs\Stock\Setup\Schema($appServices->getDbProvider()->getSchemaManager());
		$schema->generate();
		$appServices->getDbProvider()->closeConnection();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function setUp()
	{
		parent::setUp();
		$cs = new \Rbs\Commerce\Services\CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$this->sm = $cs->getStockManager();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}

	/**
	 * @return \Rbs\Stock\Documents\Sku
	 */
	protected function getTestSku()
	{
		$tm = $this->sm->getCommerceServices()->getApplicationServices()->getTransactionManager();
		$tm->begin();
		$sku = $this->sm->getCommerceServices()->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku->setCode(\Change\Stdlib\String::random(24));
		$sku->save();
		$tm->commit();
		return $sku;
	}


	public function testSetInventory()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();

		$sku = $this->getTestSku();
		$entry = $this->sm->setInventory(10, $sku);
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $entry);
		$this->assertGreaterThan(0, $entry->getId());
		$this->assertEquals(10, $entry->getLevel());
		$this->assertEquals($sku->getId(), $entry->getSku()->getId());
		$this->assertEquals(null, $entry->getWarehouse());

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->eq('sku', $sku);
		$this->assertEquals(1, $query->getCountDocuments());


		// We update the previous entry
		$reusedEntry = $this->sm->setInventory(20, $sku);
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $reusedEntry);
		$this->assertGreaterThan(0, $reusedEntry->getId());
		$this->assertEquals(20, $reusedEntry->getLevel());
		$this->assertEquals($sku->getId(), $reusedEntry->getSku()->getId());
		$this->assertEquals(null, $reusedEntry->getWarehouse());
		$this->assertEquals($entry->getId(), $reusedEntry->getId());

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->eq('sku', $sku);
		$this->assertEquals(1, $query->getCountDocuments());

		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testInventoryMovement()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();

		$sku = $this->getTestSku();
		$this->sm->setInventory(10, $sku);

		$mvtId = $this->sm->addInventoryMovement(-5, $sku);
		$this->assertGreaterThan(0, $mvtId);

		$mvtId2 = $this->sm->addInventoryMovement(-2, $sku);
		$this->assertGreaterThan($mvtId, $mvtId2);

		$level = $this->sm->getInventoryLevel($sku, null);
		$this->assertEquals(3, $level);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testReservation()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();

		$sku = $this->getTestSku();
		$this->sm->setInventory(100, $sku);

		$mvtId = $this->sm->addInventoryMovement(-5, $sku);
		$this->assertGreaterThan(0, $mvtId);

		$mvtId2 = $this->sm->addInventoryMovement(-15, $sku);
		$this->assertGreaterThan($mvtId, $mvtId2);

		$targetIdentifier = \Change\Stdlib\String::random(40);

		$res1 = new \Rbs\Stock\Std\Reservation();
		$res1->setWebStoreId(999)->setCodeSku($sku->getCode())->setQuantity(8);

		$result = $this->sm->setReservations($targetIdentifier, array($res1));
		$this->assertCount(0, $result);

		$reservations = $this->sm->getReservations($targetIdentifier);
		$this->assertCount(1, $reservations);
		/* @var $reservation \Rbs\Stock\Interfaces\Reservation */
		$reservation = $reservations[0];
		$this->assertInstanceOf('\Rbs\Stock\Interfaces\Reservation', $reservation);
		$this->assertEquals(999, $reservation->getWebStoreId());
		$this->assertEquals($sku->getCode(), $reservation->getCodeSku());
		$this->assertEquals(8, $reservation->getQuantity());

		$level = $this->sm->getInventoryLevel($sku, 999);
		$this->assertEquals(72, $level);

		$res1->setQuantity(18);
		$result = $this->sm->setReservations($targetIdentifier, array($res1));
		$this->assertCount(0, $result);

		$level = $this->sm->getInventoryLevel($sku, 999);
		$this->assertEquals(62, $level);

		$result = $this->sm->setReservations('targetIdentifier', array($res1));
		$this->assertCount(0, $result);

		$level = $this->sm->getInventoryLevel($sku, 999);
		$this->assertEquals(62 - 18, $level);

		$this->sm->unsetReservations('targetIdentifier');
		$level = $this->sm->getInventoryLevel($sku, 999);
		$this->assertEquals(62, $level);

		$this->sm->unsetReservations($targetIdentifier);
		$level = $this->sm->getInventoryLevel($sku, 999);
		$this->assertEquals(80, $level);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}
}
