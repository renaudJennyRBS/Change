<?php

namespace ChangeTests\Modules\Catalog\Services;

use Rbs\Stock\Services\StockManager;

class StockManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	/**
	 * @var \Rbs\Stock\Services\StockManager
	 */
	protected $sm;

	public static function setUpBeforeClass()
	{
		static::clearDB();
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{

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
	 * @return \Rbs\Catalog\Documents\Product
	 */
	protected function getTestProduct()
	{
		$tm = $this->sm->getCommerceServices()->getApplicationServices()->getTransactionManager();
		$tm->begin();
		$product = $this->sm->getCommerceServices()->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		$product->setLabel(\Change\Stdlib\String::random(24));
		$product->setSkucode(\Change\Stdlib\String::random(24));
		$product->save();
		$tm->commit();
		return $product;
	}


	public function testSetInventory()
	{
		$product = $this->getTestProduct();
		$entry = $this->sm->setInventory(10, $product->getSku());
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $entry);
		$this->assertGreaterThan(0, $entry->getId());
		$this->assertEquals(10, $entry->getLevel());
		$this->assertEquals($product->getSku(), $entry->getSku());
		$this->assertEquals(null, $entry->getWarehouse());
		$this->assertEquals(true, $entry->getActive());
		$this->assertEquals(null, $entry->getStartActivation());
		$this->assertEquals(null, $entry->getEndActivation());
		$this->assertEquals(StockManager::INVENTORY_UNIT_PIECE, $entry->getUnit());

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->eq('sku', $product->getSku());
		$this->assertEquals(1, $query->getCountDocuments());


		// We update the previous entry
		$reusedEntry = $this->sm->setInventory(20, $product->getSku());
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $reusedEntry);
		$this->assertGreaterThan(0, $reusedEntry->getId());
		$this->assertEquals(20, $reusedEntry->getLevel());
		$this->assertEquals($product->getSku(), $reusedEntry->getSku());
		$this->assertEquals(null, $reusedEntry->getWarehouse());
		$this->assertEquals(true, $reusedEntry->getActive());
		$this->assertEquals(null, $reusedEntry->getStartActivation());
		$this->assertEquals(null, $reusedEntry->getEndActivation());
		$this->assertEquals(StockManager::INVENTORY_UNIT_PIECE, $reusedEntry->getUnit());
		$this->assertEquals($entry->getId(), $reusedEntry->getId());

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->eq('sku', $product->getSku());
		$this->assertEquals(1, $query->getCountDocuments());

		// New entry
		$dt = new \DateTime();
		$entry = $this->sm->setInventory(10, $product->getSku(), null, StockManager::INVENTORY_UNIT_PIECE, $dt);
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $entry);
		$this->assertGreaterThan(0, $entry->getId());
		$this->assertEquals(10, $entry->getLevel());
		$this->assertEquals($product->getSku(), $entry->getSku());
		$this->assertEquals(null, $entry->getWarehouse());
		$this->assertEquals(true, $entry->getActive());
		$this->assertEquals($dt, $entry->getStartActivation());
		$this->assertEquals(null, $entry->getEndActivation());
		$this->assertEquals(StockManager::INVENTORY_UNIT_PIECE, $entry->getUnit());

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->eq('sku', $product->getSku());
		$this->assertEquals(2, $query->getCountDocuments());

		// New entry
		$dt = new \DateTime();
		$dt = $dt->add((new \DateInterval('P1Y')));
		$entry = $this->sm->setInventory(10, $product->getSku(), null, 100, $dt);
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $entry);
		$this->assertGreaterThan(0, $entry->getId());
		$this->assertEquals(10, $entry->getLevel());
		$this->assertEquals($product->getSku(), $entry->getSku());
		$this->assertEquals(null, $entry->getWarehouse());
		$this->assertEquals(true, $entry->getActive());
		$this->assertEquals($dt, $entry->getStartActivation());
		$this->assertEquals(null, $entry->getEndActivation());
		$this->assertEquals(100, $entry->getUnit());
		$this->assertNotEquals($entry->getId(), $reusedEntry->getId());

		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Stock_InventoryEntry');
		$query->eq('sku', $product->getSku());
		$this->assertEquals(3, $query->getCountDocuments());
	}
}
