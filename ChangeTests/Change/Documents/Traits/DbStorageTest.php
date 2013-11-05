<?php
namespace ChangeTests\Change\Documents\Traits;

use Change\Documents\AbstractDocument;

/**
 * @name \ChangeTests\Change\Documents\Traits\DbStorageTest
 */
class DbStorageTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	protected function tearDown()
	{
		parent::tearDown();
		$this->closeDbConnection();
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		$manager = $this->getApplicationServices()->getDocumentManager();
		$manager->reset();
		return $manager;
	}

	public function testTransaction()
	{
		/* @var $document \Project\Tests\Documents\Basic */
		$manager = $this->getDocumentManager();
		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$document->setPStr('pStr');
		try
		{
			$document->create();
			$this->fail('RuntimeException expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('No transaction started!', $e->getMessage());
		}

		try
		{
			$document->initialize(1, AbstractDocument::STATE_LOADED);
			$document->setPStr('pStr2');
			$document->update();
			$this->fail('RuntimeException: expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('No transaction started!', $e->getMessage());
		}
	}

	public function testDocument()
	{
		$manager = $this->getDocumentManager();

		/* @var $document \Project\Tests\Documents\Basic */
		$this->getApplicationServices()->getTransactionManager()->begin();

		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertEquals(AbstractDocument::STATE_NEW, $document->getPersistentState());
		$this->assertTrue($document->isNew());
		$this->assertFalse($document->isLoaded());
		$this->assertFalse($document->isDeleted());

		$this->assertLessThan(0, $document->getId());
		$document->setPStr('Required');
		$document->create();
		$this->assertEquals(AbstractDocument::STATE_LOADED, $document->getPersistentState());
		$this->assertTrue($document->isLoaded());
		$this->assertGreaterThan(0, $document->getId());
		try
		{
			$document->create();
			$this->fail('RuntimeException Expected');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Document is not new', $e->getMessage());
		}

		$definedId = $document->getId() + 10;
		/* @var $document2 \Project\Tests\Documents\Basic */
		$document2 = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertLessThan(0, $document2->getId());
		$document2->initialize($definedId);
		$this->assertEquals($definedId, $document2->getId());
		$this->assertEquals(AbstractDocument::STATE_NEW, $document2->getPersistentState());
		$document2->setPStr('Required');
		$document2->create();
		$this->assertEquals(AbstractDocument::STATE_LOADED, $document2->getPersistentState());

		/* @var $document3 \Project\Tests\Documents\Basic */
		$document3 = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertLessThan(0, $document3->getId());
		$document3->setPStr('Required');
		$document3->create();
		$this->assertEquals($definedId + 1, $document3->getId());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $document3->getPersistentState());

		$document2->delete();
		$this->assertEquals(AbstractDocument::STATE_DELETED, $document2->getPersistentState());
		$this->assertTrue($document2->isDeleted());

		$document->setPStr('Document Label');
		$this->assertTrue($document->isPropertyModified('pStr'));

		$document->update();
		$this->assertFalse($document->isPropertyModified('pStr'));

		$cachedDoc = $manager->getDocumentInstance($document->getId());
		$this->assertTrue($cachedDoc === $document);
		$this->assertEquals(AbstractDocument::STATE_LOADED, $cachedDoc->getPersistentState());

		$manager->reset();

		/* @var $newDoc1 \Project\Tests\Documents\Basic */
		$newDoc1 = $manager->getDocumentInstance($document->getId());
		$this->assertNotSame($document, $newDoc1);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $newDoc1);
		$this->assertEquals($document->getId(), $newDoc1->getId());
		$this->assertEquals(AbstractDocument::STATE_INITIALIZED, $newDoc1->getPersistentState());

		$newDoc1->load();
		$this->assertEquals(AbstractDocument::STATE_LOADED, $newDoc1->getPersistentState());
		$this->assertEquals('Document Label', $newDoc1->getPStr());

		$metas = array('k1' => 'v1', 'k2' => array(45, 46, 50));
		$newDoc1->setMetas($metas);
		$newDoc1->saveMetas();

		$manager->reset();

		/* @var $newDocMeta \Project\Tests\Documents\Basic */
		$newDocMeta = $manager->getDocumentInstance($newDoc1->getId());
		$this->assertNotSame($newDoc1, $newDocMeta);
		$this->assertEquals($metas, $newDocMeta->getMetas());

		/* @var $newDoc \Project\Tests\Documents\Basic */
		$newDoc = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$tmpId = $newDoc->getId();
		$this->assertLessThan(0, $tmpId);
		$this->assertNull($manager->getDocumentInstance($tmpId));
		$this->assertNull($manager->getDocumentInstance(-5000));

		$newDoc->setPStr('required');
		$newDoc->create();

		$finalId = $newDoc->getId();
		$this->assertGreaterThan(0, $finalId);

		$this->assertSame($newDoc, $manager->getDocumentInstance($finalId));
		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testPropertyDocumentIds()
	{
		$manager = $this->getDocumentManager();

		$this->getApplicationServices()->getTransactionManager()->begin();

		/* @var $sd1 \Project\Tests\Documents\DocProps */
		$sd1 = $manager->getNewDocumentInstanceByModelName('Project_Tests_DocProps');
		$sd1->create();

		/* @var $sd2 \Project\Tests\Documents\DocProps */
		$sd2 = $manager->getNewDocumentInstanceByModelName('Project_Tests_DocProps');
		$sd2->create();

		/* @var $basic \Project\Tests\Documents\DocProps */
		$basic = $manager->getNewDocumentInstanceByModelName('Project_Tests_DocProps');

		$basic->getPDocArr()->add($sd1);
		$basic->getPDocArr()->add($sd2);
		$basic->create();

		$this->assertEquals(array($sd1->getId(), $sd2->getId()), $basic->getPDocArrIds());
		$this->getApplicationServices()->getTransactionManager()->commit();

		/* @var $b2 \Project\Tests\Documents\DocProps */
		$b2 = $manager->getDocumentInstance($basic->getId());
		$this->assertNotSame($basic, $b2);
		$this->assertEquals(array($sd1->getId(), $sd2->getId()), $b2->getPDocArrIds());
	}
}