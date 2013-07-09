<?php

class AbstractProductTest extends \ChangeTests\Change\TestAssets\TestCase
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

	public function testCategoryList()
	{
		/* @var $product \Rbs\Catalog\Documents\AbstractProduct */
		$product = $this->getNewReadonlyDocument('Rbs_Catalog_AbstractProduct', 10000);
		$conditionId1 = 10500;
		$conditionId2 = 10501;

		$this->assertEquals(0, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$this->assertCount(0, $product->getCategoryList($conditionId1, 0, 10));

		// -- setCategoryIds()

		// Same priority.
		$product->setCategoryIds($conditionId1, array(10001, 10002, 10003), 5);
		$this->assertEquals(3, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$list = $product->getCategoryList($conditionId1, 0, 10);
		$this->assertCount(3, $list);
		$expected = array(
			array('category_id' => 10001, 'priority' => 5),
			array('category_id' => 10002, 'priority' => 5),
			array('category_id' => 10003, 'priority' => 5)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities.
		$product->setCategoryIds($conditionId1, array(10011, 10012, 10013, 10014), array(1, 5, 1, 125));
		$this->assertEquals(4, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$list = $product->getCategoryList($conditionId1, 0, 10);
		$this->assertCount(4, $list);
		$expected = array(
			array('category_id' => 10011, 'priority' => 1),
			array('category_id' => 10012, 'priority' => 5),
			array('category_id' => 10013, 'priority' => 1),
			array('category_id' => 10014, 'priority' => 125)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// -- removeCategoryIds()

		$product->removeCategoryIds($conditionId1, array(10012, 10013));
		$this->assertEquals(2, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$list = $product->getCategoryList($conditionId1, 0, 10);
		$this->assertCount(2, $list);
		$expected = array(
			array('category_id' => 10011, 'priority' => 1),
			array('category_id' => 10014, 'priority' => 125)
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// -- addCategoryIds()

		// Same priority.
		$product->addCategoryIds($conditionId1, array(10011, 10025, 10026), 75);
		$this->assertEquals(4, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$list = $product->getCategoryList($conditionId1, 0, 10);
		$this->assertCount(4, $list);
		$expected = array(
			array('category_id' => 10011, 'priority' => 1), // id 10011 already exists, the priority is not modified.
			array('category_id' => 10014, 'priority' => 125),
			array('category_id' => 10025, 'priority' => 75),
			array('category_id' => 10026, 'priority' => 75),
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// Different priorities.
		$product->addCategoryIds($conditionId1, array(10011, 10027), 89);
		$this->assertEquals(5, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$list = $product->getCategoryList($conditionId1, 0, 10);
		$this->assertCount(5, $list);
		$expected = array(
			array('category_id' => 10011, 'priority' => 1), // id 10011 already exists, the priority is not modified.
			array('category_id' => 10014, 'priority' => 125),
			array('category_id' => 10025, 'priority' => 75),
			array('category_id' => 10026, 'priority' => 75),
			array('category_id' => 10027, 'priority' => 89),
		);
		foreach ($list as $row)
		{
			$this->assertContains($row, $expected);
		}

		// -- removeAllCategoryIds()

		$product->removeAllCategoryIds($conditionId1);
		$this->assertEquals(0, $product->countCategories($conditionId1));
		$this->assertEquals(0, $product->countCategories($conditionId2));
		$this->assertCount(0, $product->getCategoryList($conditionId1, 0, 10));
	}
}