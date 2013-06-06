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
		$dm = $this->getDocumentServices()->getDocumentManager();
		$item = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
		/* @var $item \Rbs\Collection\Documents\Item */
		$item->setLabel('Test1');
		$item->setValue('test1');
		$item->save();

		$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
		/* @var $collection \Rbs\Collection\Documents\Collection */
		$collection->setCode('rbsCollectionTest1');
		$collection->setLabel('RbsCollectionTest1');
		$collection->addItems($item);
		$collection->save();

		$foundItem = $collection->getItemByValue('test1');
		$this->assertEquals('Test1', $foundItem->getLabel());
		$foundItem = $collection->getItemByValue('test0');
		$this->assertNull($foundItem);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testConstraintUnique()
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
		/* @var $collection \Rbs\Collection\Documents\Collection */
		$collection->setCode('rbsCollectionTestForUnique');
		$collection->setLabel('RbsCollectionTest1');
		$collection->save();

		$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
		/* @var $collection \Rbs\Collection\Documents\Collection */
		$collection->setCode('rbsCollectionTestForUnique');
		$collection->setLabel('RbsCollectionTestFailed');
		$collection->save();
	}
}