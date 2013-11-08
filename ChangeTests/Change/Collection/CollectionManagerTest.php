<?php
namespace ChangeTests\Change\Collection;

use Change\Collection\CollectionManager;

/**
 * @name \ChangeTests\Change\Collection\CollectionManagerTest
 */
class CollectionManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return CollectionManager
	 */
	protected function getCollectionManager()
	{
		$collectionManager = $this->getApplicationServices()->getCollectionManager();
		return $collectionManager;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('Change\Collection\CollectionManager', $this->getCollectionManager());
	}

	public function testGetCollection()
	{
		$cm = $this->getCollectionManager();
		$this->assertNull($cm->getCollection('test'));

		$callBack = function(\Change\Events\Event $event)
		{
			if ($event->getParam('code') === 'test')
			{
				$event->setParam('collection', new Fake_Collection_5421564515854());
			}
		};
		$cbh = $cm->getEventManager()->attach(CollectionManager::EVENT_GET_COLLECTION, $callBack);

		$col = $cm->getCollection('test');
		$this->assertInstanceOf('\Change\Collection\CollectionInterface', $col);

		$this->assertNull($cm->getCollection('test2'));

		$cm->getEventManager()->detach($cbh);
		$this->assertNull($cm->getCollection('test'));
	}

	public function testGetCodes()
	{
		$cm = $this->getCollectionManager();
		$codes = $cm->getCodes();
		$this->assertCount(0, $codes);

		$callback = function($event)
		{
			$event->setParam('codes', array('test1', 'test2'));
		};
		$cm->getEventManager()->attach(CollectionManager::EVENT_GET_CODES, $callback);

		$codes = $cm->getCodes();
		$this->assertCount(2, $codes);
		$this->assertEquals('test1', $codes[0]);
		$this->assertEquals('test2', $codes[1]);
	}
}

class Fake_Collection_5421564515854 implements \Change\Collection\CollectionInterface
{

	/**
	 * @return \Change\Collection\ItemInterface[]
	 */
	public function getItems()
	{
		// TODO: Implement getItems() method.
	}

	/**
	 * @param mixed $value
	 * @return \Change\Collection\ItemInterface|null
	 */
	public function getItemByValue($value)
	{
		// TODO: Implement getItemByValue() method.
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		// TODO: Implement getCode() method.
	}
}