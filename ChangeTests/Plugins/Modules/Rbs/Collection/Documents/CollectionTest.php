<?php

class CollectionTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testGetItemByValue()
	{
		$this->getApplicationServices()->getTransactionManager()->begin();
		$dm = $this->getApplicationServices()->getDocumentManager();

		$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
		/* @var $collection \Rbs\Collection\Documents\Collection */
		$collection->setCode('rbsCollectionTest1');
		$collection->setLabel('RbsCollectionTest1');

		$item = $collection->newCollectionItem();
		$item->setLabel('Test1');
		$item->setValue('test1');

		$collection->setItems(array($item));
		$collection->save();

		$foundItem = $collection->getItemByValue('test1');
		$this->assertEquals('Test1', $foundItem->getLabel());
		$foundItem = $collection->getItemByValue('test0');
		$this->assertNull($foundItem);

		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testConstraintUnique()
	{
		$appServices = $this->getApplicationServices();

		$appServices->getTransactionManager()->begin();

		$dm = $appServices->getDocumentManager();
		$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
		/* @var $collection \Rbs\Collection\Documents\Collection */
		$collection->setCode('rbsCollectionTestForUnique');
		$collection->setLabel('RbsCollectionTest1');
		$collection->save();

		try
		{
			$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
			/* @var $collection \Rbs\Collection\Documents\Collection */
			$collection->setCode('rbsCollectionTestForUnique');
			$collection->setLabel('RbsCollectionTestFailed');
			$collection->save();
			$this->fail('Exception expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertNotEmpty($e->getMessage());
		}

		$appServices->getTransactionManager()->commit();
	}
}