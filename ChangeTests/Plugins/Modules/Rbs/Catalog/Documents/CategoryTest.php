<?php

class CategoryTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		$appServices = static::initDb();
		static::initDocumentsClasses();

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
		$conditionId1 = 10500;
		$conditionId2 = 10501;

		$this->assertEquals(0, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$this->assertCount(0, $category->getProductList($conditionId1, 0, 10));

		// -- setProductIds()

		// Same priority.
		$category->setProductIds($conditionId1, array(10001, 10002, 10003), 5);
		$this->assertEquals(3, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(3, $list);
		$expected = array(
			array('product_id' => 10001, 'priority' => 5),
			array('product_id' => 10002, 'priority' => 5),
			array('product_id' => 10003, 'priority' => 5)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities.
		$category->setProductIds($conditionId1, array(10011, 10012, 10013, 10014), array(1, 5, 1, 125));
		$this->assertEquals(4, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(4, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 1),
			array('product_id' => 10012, 'priority' => 5),
			array('product_id' => 10013, 'priority' => 1),
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
			array('product_id' => 10014, 'priority' => 125)
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
			array('product_id' => 10011, 'priority' => 1), // id 10011 already exists, the priority is not modified.
			array('product_id' => 10014, 'priority' => 125),
			array('product_id' => 10025, 'priority' => 75),
			array('product_id' => 10026, 'priority' => 75),
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities.
		$category->addProductIds($conditionId1, array(10011, 10027), 89);
		$this->assertEquals(5, $category->countProducts($conditionId1));
		$this->assertEquals(0, $category->countProducts($conditionId2));
		$list = $category->getProductList($conditionId1, 0, 10);
		$this->assertCount(5, $list);
		$expected = array(
			array('product_id' => 10011, 'priority' => 1), // id 10011 already exists, the priority is not modified.
			array('product_id' => 10014, 'priority' => 125),
			array('product_id' => 10025, 'priority' => 75),
			array('product_id' => 10026, 'priority' => 75),
			array('product_id' => 10027, 'priority' => 89),
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
	}
}