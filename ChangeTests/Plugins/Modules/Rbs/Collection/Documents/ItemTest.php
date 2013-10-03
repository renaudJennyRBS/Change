<?php

class ItemTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var \Rbs\Collection\Documents\Item
	 */
	protected $item;

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
		$this->item = $this->createAnItem();
	}

	protected function tearDown()
	{
		$this->deleteAnItem($this->item);
		parent::tearDown();
	}

	public function testOnUpdate()
	{
		//first try with a non locked item
		$this->item->setValue('new');
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$this->item->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$dm = $this->getDocumentServices()->getDocumentManager();
		/* @var $item \Rbs\Collection\Documents\Item */
		$item = $dm->getDocumentInstance($this->item->getId());
		$this->assertNotNull($item);
		$this->assertEquals('new', $item->getValue());

		//Now try with a locked item
		$item = $this->createAnItem(true);
		$item->setValue('new');
		try
		{
			$tm->begin();
			$item->update();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		/* @var $item \Rbs\Collection\Documents\Item */
		$item = $dm->getDocumentInstance($item->getId());
		$this->assertNotEquals('new', $item->getValue());
		//in fact, locked item keep his old value
		$this->assertEquals('test', $item->getValue());
	}

	/**
	 * @throws Exception
	 */
	public function testOnDelete()
	{
		//first try with a non locked item
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$this->item->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		//Now try with a locked item
		$item = $this->createAnItem(true);
		$this->setExpectedException('\\RuntimeException', 'can not delete locked item');
		$item->delete();
	}

	public function testUpdateRestDocumentLink()
	{
		$documentLink = new \Change\Http\Rest\Result\DocumentLink(new \Change\Http\UrlManager(new \Zend\Uri\Http()), $this->item, \Change\Http\Rest\Result\DocumentLink::MODE_PROPERTY);
		$result = $documentLink->toArray();
		$this->assertArrayHasKey('locked', $result);
		$this->assertNotNull($result['locked']);
		$this->assertArrayHasKey('value', $result);
		$this->assertNotNull($result['value']);
	}

	/**
	 * @param boolean $locked
	 * @return \Rbs\Collection\Documents\Item
	 * @throws Exception
	 */
	protected function createAnItem($locked = false)
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		$item = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
		/* @var $item \Rbs\Collection\Documents\Item */
		$item->setLabel('Test');
		$item->setValue('test');
		$item->setLocked($locked);
		try
		{
			$tm->begin();
			$item->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$this->assertTrue($item->getId() > 0);
		return $item;
	}

	/**
	 * @param \Rbs\Collection\Documents\Item $item
	 * @throws Exception
	 */
	protected function deleteAnItem($item)
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$tm = $this->getApplicationServices()->getTransactionManager();

		try
		{
			$tm->begin();
			$item->delete();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
	}
}