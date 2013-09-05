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
		$collection->setItems(array($item));
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

	public function testOnUpdate()
	{
		//first create a non locked collection and update it
		$collection1 = $this->createACollection('collection1', 5);
		$this->assertFalse($collection1->getLocked());
		$itemIds = $collection1->getItemsIds();
		$code = $collection1->getCode();
		//now we replace items and code
		$collection1->setItems($this->createItems('reTest', 5));
		$collection1->setCode('newCollection1');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$collection1->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->fail('cannot update collection with this error: ' . $e->getMessage());
		}
		$dm = $this->getDocumentServices()->getDocumentManager();
		$collection1 = $dm->getDocumentInstance($collection1->getId());
		$this->assertNotEquals($code, $collection1->getCode());
		$this->assertNotEquals($itemIds, $collection1->getItemsIds());

		//now we do the same test but with a locked collection
		$collection2 = $this->createACollection('collection2', 5, true);
		$this->assertTrue($collection2->getLocked());
		$itemIds = $collection2->getItemsIds();
		$code = $collection2->getCode();
		//We change the code but that wont work because collection is locked
		$collection2->setCode('newCollection2');
		//now we add items, that will work because we just add one
		$newItem = $this->createItems('reTest', 1)[0];
		$newItems = $collection2->getItems()->add($newItem);
		$collection2->setItems($newItems->toArray());
		try
		{
			$tm->begin();
			$collection2->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->fail('cannot update collection with this error: ' . $e->getMessage());
		}
		$dm = $this->getDocumentServices()->getDocumentManager();
		/* @var $collection2 \Rbs\Collection\Documents\Collection */
		$collection2 = $dm->getDocumentInstance($collection2->getId());
		$this->assertEquals($code, $collection2->getCode());
		//Items are not equals because we add one
		$this->assertNotEquals($itemIds, $collection2->getItemsIds());
		//We check if this is the good one we added
		$itemIdsDiff = array_diff($collection2->getItemsIds(), $itemIds);
		$this->assertCount(1, $itemIdsDiff);
		$this->assertEquals($newItem->getId(), array_pop($itemIdsDiff));

		//next we check if we try to delete an element from items that doesn't work (because it's locked)
		//keep item ids to compare after update and remove one of them
		$itemIds = $collection2->getItemsIds();
		$newItems = $collection2->getItems();
		$count = $newItems->count();
		$newItems->rewind();
		$newItems->remove($newItems->current());
		$this->assertNotEquals($count, $newItems->count());
		try
		{
			$tm->begin();
			//update will throw an exception because we cannot remove an item when a collection is locked
			$this->setExpectedException('\RuntimeException', 'can not removed locked item from collection');
			$collection2->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
		}

	}

	public function testOnDelete()
	{
		//first create a non locked collection and delete it
		$collection1 = $this->createACollection('collectionToDelete', 5);
		$this->assertFalse($collection1->getLocked());
		$itemIds = $collection1->getItemsIds();
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$collection1->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			$tm->rollBack($e);
			$this->fail('cannot delete collection with this error: ' . $e->getMessage());
		}
		$dm = $this->getDocumentServices()->getDocumentManager();
//		$collection = $dm->getDocumentInstance($collection1->getId());
		//check if items are all been deleted
		foreach ($itemIds as $itemId)
		{
//			$this->assertNull($dm->getDocumentInstance($itemId));
		}

	}

	/**
	 * @param string $collectionCode
	 * @param integer $numberOfItems
	 * @param boolean $locked
	 * @throws \Exception
	 * @return \Rbs\Collection\Documents\Collection
	 */
	protected function createACollection($collectionCode, $numberOfItems, $locked = false)
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		$items = $this->createItems('test', 5);

		$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
		/* @var $collection \Rbs\Collection\Documents\Collection */
		$collection->setCode($collectionCode);
		$collection->setLabel('rbs' . ucfirst($collectionCode));
		$collection->setItems($items);
		$collection->setLocked($locked);
		try
		{
			$tm->begin();
			$collection->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $collection;
	}

	/**
	 * @param string $prefix
	 * @param integer $numberOfItems
	 * @throws \Exception
	 * @return array
	 */
	protected function createItems($prefix, $numberOfItems)
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();
		$items = [];
		for ($i = 0; $i < $numberOfItems; $i++)
		{
			$item = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
			/* @var $item \Rbs\Collection\Documents\Item */
			$item->setLabel(ucfirst($prefix) . $i);
			$item->setValue($prefix . $i);
			try
			{
				$tm->begin();
				$item->save();
				$tm->commit();
				$items[] = $item;
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
		return $items;
	}
}