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
		static::initDocumentsDb();
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
	}
}
