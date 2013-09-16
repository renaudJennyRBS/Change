<?php

class CollectionResolverTest extends \ChangeTests\Change\TestAssets\TestCase
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

	public function testGetCollection()
	{
		$this->createFiveFakeCollections();

		$collectionResolver = new \Rbs\Collection\Events\CollectionResolver();
		$event = new \Zend\EventManager\Event();
		$event->setParam('code', 'rbsCollectionTest1');
		$event->setParam('documentServices', $this->getDocumentServices());
		$collectionResolver->getCollection($event);
		$collection = $event->getParam('collection');

		/* @var $collection \Rbs\Collection\Documents\Collection */
		$this->assertEquals('collection1', $collection->getLabel());
	}

	/**
	 * @depends testGetCollection
	 */
	public function testGetCodes()
	{
		$event = new \Zend\EventManager\Event();
		$event->setParam('documentServices', $this->getDocumentServices());
		$collectionResolver = new \Rbs\Collection\Events\CollectionResolver();
		$collectionResolver->getCodes($event);
		$codes = $event->getParam('codes');
		$countCodes = count($codes);
		$this->assertGreaterThanOrEqual(5, $countCodes);

		$this->assertContains('rbsCollectionTest0', $codes);
		$this->assertContains('rbsCollectionTest1', $codes);
		$this->assertContains('rbsCollectionTest2', $codes);
		$this->assertContains('rbsCollectionTest3', $codes);
		$this->assertContains('rbsCollectionTest4', $codes);

		//Test adding of five new collections (need to be merged with current collections)
		$this->createFiveFakeCollections(5);
		$collectionResolver->getCodes($event);
		$codes = $event->getParam('codes');

		$this->assertCount($countCodes + 5, $codes);

		$this->assertContains('rbsCollectionTest5', $codes);
		$this->assertContains('rbsCollectionTest6', $codes);
		$this->assertContains('rbsCollectionTest7', $codes);
		$this->assertContains('rbsCollectionTest8', $codes);
		$this->assertContains('rbsCollectionTest9', $codes);
	}

	/**
	 * @param int $beginIndex
	 */
	protected function createFiveFakeCollections($beginIndex = 0)
	{
		$dm = $this->getDocumentServices()->getDocumentManager();
		$end = $beginIndex + 5;
		for ($i = $beginIndex; $i < $end; $i++)
		{

			/* @var $collection \Rbs\Collection\Documents\Collection */
			$collection = $dm->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
			$collection->setCode('rbsCollectionTest' . $i);
			$collection->setLabel('collection' . $i);
			$collection->save();
		}
	}

}