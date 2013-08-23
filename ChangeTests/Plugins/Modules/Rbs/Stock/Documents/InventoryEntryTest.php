<?php

class InventoryEntryTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Rbs\Stock\Documents\InventoryEntry
	 */
	protected $inventoryEntry;

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
		$this->inventoryEntry = $this->createAnInventoryEntry();
	}

	protected function tearDown()
	{
		$this->deleteAnInventoryEntry($this->inventoryEntry);
		$this->closeDbConnection();
		parent::tearDown();
	}

	public function testGetLabel()
	{
		$this->assertEquals('test', $this->inventoryEntry->getLabel());
	}

	public function testSetLabel()
	{
		$inventoryEntry = $this->inventoryEntry->setLabel('something');
		$this->assertInstanceOf('\\Rbs\\Stock\\Documents\\InventoryEntry', $inventoryEntry);
		$this->assertNotEquals('something', $inventoryEntry->getLabel());
		$this->assertEquals('test', $inventoryEntry->getLabel());
	}

	/**
	 * @return \Rbs\Stock\Documents\InventoryEntry
	 * @throws Exception
	 */
	protected function createAnInventoryEntry()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		/* @var $sku \Rbs\Stock\Documents\Sku */
		$sku = $dm->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
		$sku->setCode('test');
		try
		{
			$tm->begin();
			$sku->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$this->assertTrue($sku->getId() > 0);

		/* @var $inventoryEntry \Rbs\Stock\Documents\InventoryEntry */
		$inventoryEntry = $dm->getNewDocumentInstanceByModelName('Rbs_Stock_InventoryEntry');
		$inventoryEntry->setSku($sku);
		try
		{
			$tm->begin();
			$inventoryEntry->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$this->assertTrue($inventoryEntry->getId() > 0);
		return $inventoryEntry;
	}

	/**
	 * @param \Rbs\Stock\Documents\InventoryEntry $inventoryEntry
	 * @throws \Exception
	 */
	protected function deleteAnInventoryEntry($inventoryEntry)
	{
		if ($inventoryEntry)
		{
			$tm = $this->getApplicationServices()->getTransactionManager();

			try
			{
				$tm->begin();
				//delete sku in fact, that will delete entry whit it
				$inventoryEntry->getSku()->delete();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}
}
