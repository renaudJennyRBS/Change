<?php

class CategoryTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$appServices = static::initDocumentsDb();

		$schemaManager = $appServices->getDbProvider()->getSchemaManager();
		$schema = new \Rbs\Catalog\Setup\Schema($schemaManager);
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
		$this->getApplicationServices()->getTransactionManager()->begin();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->getApplicationServices()->getTransactionManager()->commit();
		$this->closeDbConnection();
	}

	public function testProductList()
	{
		/* @var $category \Rbs\Catalog\Documents\Category */
		$category = $this->getNewReadonlyDocument('Rbs_Catalog_Category', 10000);
		$category->setProductSortOrder('auto'); // No sort order, to not join with the product table.
		$conditionId1 = 10500;
		$conditionId2 = 10501;

		$this->assertEquals(0, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$this->assertCount(0, $category->getProductList($conditionId1, 0, 10));

		// -- setProductIds()

		// Same priority.
		$category->setProductIds($conditionId1, array(10001, 10002, 10003), 1);
		$this->assertEquals(3, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(3, $list);
		$expected = array(
			array('product_id' => 10001, 'priority' => 3),
			array('product_id' => 10002, 'priority' => 2),
			array('product_id' => 10003, 'priority' => 1)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities.
		$category->setProductIds($conditionId1, array(10011, 10012, 10013, 10014), array(1, 5, 2, 125));
		$this->assertEquals(4, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(4, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 1),
			array('product_id' => 10012, 'priority' => 5),
			array('product_id' => 10013, 'priority' => 2),
			array('product_id' => 10014, 'priority' => 125)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// -- removeProductIds()

		$category->removeProductIds($conditionId1, array(10012, 10013));
		$this->assertEquals(2, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(2, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 1),
			array('product_id' => 10014, 'priority' => 123) // 10012 and 10013 priorities were inferior to 125, so 10014 is moved.
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// -- addProductIds()

		// Same priority.
		$category->addProductIds($conditionId1, array(10011, 10025, 10026), 75);
		$this->assertEquals(4, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(4, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 77), // id 10011 already exists, the priority is updated.
			array('product_id' => 10014, 'priority' => 124), // moving to a free place 10011 decrements 10014's priority, then inserting 10025 and 10026 each increments it.
			array('product_id' => 10025, 'priority' => 76),
			array('product_id' => 10026, 'priority' => 75)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities (at free places).
		$category->addProductIds($conditionId1, array(10011, 10027), array(89, 90));
		$this->assertEquals(5, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(5, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 89), // id 10011 already exists, the priority is updated.
			array('product_id' => 10014, 'priority' => 123), // moving 10011 to a free place decrements 10014's priority.
			array('product_id' => 10025, 'priority' => 76),
			array('product_id' => 10026, 'priority' => 75),
			array('product_id' => 10027, 'priority' => 90)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities (at non-free places).
		$category->addProductIds($conditionId1, array(10011, 10028), array(123, 123));
		$this->assertEquals(6, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(6, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 124), // id 10011 already exists, the priority is updated.
			array('product_id' => 10014, 'priority' => 122), // moving 10011 to a free place decrements 10014's priority.
			array('product_id' => 10025, 'priority' => 76),
			array('product_id' => 10026, 'priority' => 75),
			array('product_id' => 10027, 'priority' => 89), // moving 10027 to a free place decrements 10027's priority.
			array('product_id' => 10028, 'priority' => 123)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Move to top.
		$category->addProductIds($conditionId1, array(10014, 10029), 'top');
		$this->assertEquals(7, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(7, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 123), // moving 10014 to a free place decrements 10011's priority.
			array('product_id' => 10014, 'priority' => 125),
			array('product_id' => 10025, 'priority' => 76),
			array('product_id' => 10026, 'priority' => 75),
			array('product_id' => 10027, 'priority' => 89),
			array('product_id' => 10028, 'priority' => 122), // moving 10014 to a free place decrements 10011's priority.
			array('product_id' => 10029, 'priority' => 124)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// -- removeAllProductIds()

		$category->removeAllProductIds($conditionId1);
		$this->assertEquals(0, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$this->assertCount(0, $category->getProductList($conditionId1, 0, 10));

		// TODO: test non-highlighted products sort order.
	}
}