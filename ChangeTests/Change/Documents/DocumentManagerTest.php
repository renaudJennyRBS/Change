<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\DocumentManager;

class DocumentManagerTest extends \ChangeTests\Change\TestAssets\TestCase
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
	protected function getObject()
	{
		$manager = $this->getDocumentServices()->getDocumentManager();
		$manager->reset();
		return $manager;
	}
	

	public function testGetNewDocumentInstance()
	{
		$manager = $this->getObject();

		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $document);
		$this->assertEquals(-1, $document->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document->getPersistentState());
		
		$model = $document->getDocumentModel();
		
		$document2 = $manager->getNewDocumentInstanceByModel($model);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $document2);
		$this->assertEquals(-2, $document2->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document2->getPersistentState());

		/* @var $document3 \Project\Tests\Documents\DocStateless */
		$document3 = $manager->getNewDocumentInstanceByModelName('Project_Tests_DocStateless');
		$this->assertInstanceOf('\Project\Tests\Documents\DocStateless', $document3);
		$this->assertInstanceOf('\Project\Tests\Documents\DocStateless', $document3);
		$this->assertArrayHasKey('id', $document3->getData());
		$this->assertArrayHasKey('CreationDate', $document3->getData());
	}

	public function testTransaction()
	{
		/* @var $document \Project\Tests\Documents\Basic */
		$manager = $this->getObject();
		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		try
		{
			$manager->assignId($document);
			$this->fail('RuntimeException: Transaction not started');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals(121003, $e->getCode());
		}

		try
		{
			$document->initialize(1, DocumentManager::STATE_NEW);
			$manager->insertDocument($document);
			$this->fail('RuntimeException: Transaction not started');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals(121003, $e->getCode());
		}

		try
		{
			$document->initialize(1, DocumentManager::STATE_LOADED);
			$manager->updateDocument($document);
			$this->fail('RuntimeException: Transaction not started');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals(121003, $e->getCode());
		}
	}

	public function testDocument()
	{
		$manager = $this->getObject();

		/* @var $document \Project\Tests\Documents\Basic */
		$this->getApplicationServices()->getTransactionManager()->begin();

		$document = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertLessThan(0, $document->getId());
		$manager->assignId($document);
		$this->assertGreaterThan(0, $document->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document->getPersistentState());
		
		$definedId = $document->getId() + 10;
		/* @var $document2 \Project\Tests\Documents\Basic */
		$document2 = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertLessThan(0, $document2->getId());
		$document2->initialize($definedId);
		$manager->assignId($document2);
		$this->assertEquals($definedId, $document2->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document2->getPersistentState());

		/* @var $document3 \Project\Tests\Documents\Basic */
		$document3 = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertLessThan(0, $document3->getId());
		$manager->assignId($document3);
		$this->assertEquals($definedId + 1, $document3->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document3->getPersistentState());
		
		$manager->insertDocument($document);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document->getPersistentState());
		
		$manager->insertDocument($document2);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document2->getPersistentState());
		
		$manager->insertDocument($document3);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document3->getPersistentState());
			
		$manager->deleteDocument($document2);
		$this->assertEquals(DocumentManager::STATE_DELETED, $document2->getPersistentState());

		$document->setPStr('Document Label');
		$this->assertTrue($document->isPropertyModified('pStr'));
		
		$manager->updateDocument($document);
		$this->assertFalse($document->isPropertyModified('pStr'));
		
		$cachedDoc = $manager->getDocumentInstance($document->getId());
		$this->assertTrue($cachedDoc === $document);
		$this->assertEquals(DocumentManager::STATE_LOADED, $cachedDoc->getPersistentState());
		
		$manager->reset();

		/* @var $newDoc1 \Project\Tests\Documents\Basic */
		$newDoc1 = $manager->getDocumentInstance($document->getId());
		$this->assertNotSame($document, $newDoc1);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $newDoc1);
		$this->assertEquals($document->getId(), $newDoc1->getId());
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $newDoc1->getPersistentState());
			
		$manager->loadDocument($newDoc1);
		$this->assertEquals(DocumentManager::STATE_LOADED, $newDoc1->getPersistentState());
		$this->assertEquals('Document Label', $newDoc1->getPStr());
		
		$metas = array('k1' => 'v1', 'k2' => array(45, 46, 50));
		$manager->saveMetas($newDoc1, $metas);
		
		$metasLoaded = $manager->loadMetas($newDoc1);
		$this->assertEquals($metas, $metasLoaded);

		/* @var $newDoc \Project\Tests\Documents\Basic */
		$newDoc = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$tmpId = $newDoc->getId();
		$this->assertLessThan(0, $tmpId);
		$this->assertNull($manager->getDocumentInstance($tmpId));
		$this->assertNull($manager->getDocumentInstance(-5000));

		$manager->assignId($newDoc);
		
		$finalId = $newDoc->getId();
		$this->assertGreaterThan(0, $finalId);

		$this->assertSame($newDoc, $manager->getDocumentInstance($finalId));
		$this->getApplicationServices()->getTransactionManager()->commit();
	}

	public function testI18nDocument()
	{

		$this->getApplicationServices()->getTransactionManager()->begin();

		$manager = $this->getObject();
		/* @var $localized \Project\Tests\Documents\Localized */
		$localized = $manager->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$localizedI18nPartFr = $localized->getCurrentLocalization();

		$tmpId = $localized->getId();
		$this->assertNotNull($localizedI18nPartFr);
		$this->assertEquals($tmpId, $localizedI18nPartFr->getId());
		$this->assertEquals('fr_FR', $localizedI18nPartFr->getLCID());
		$this->assertEquals(DocumentManager::STATE_NEW, $localizedI18nPartFr->getPersistentState());
		
		$manager->assignId($localized);
		$manager->insertDocument($localized);
		$this->assertEquals(DocumentManager::STATE_LOADED, $localized->getPersistentState());
		
		$this->assertNotEquals($tmpId, $localized->getId());

		$localized->saveCurrentLocalization();

		$this->assertEquals($localized->getId(), $localizedI18nPartFr->getId());
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedI18nPartFr->getPersistentState());

		$localized->setPLStr('Localized Label');
		$this->assertTrue($localizedI18nPartFr->isPropertyModified('pLStr'));
		$this->assertTrue($localized->isPropertyModified('pLStr'));

		$localized->save();
		$this->assertFalse($localizedI18nPartFr->isPropertyModified('pLStr'));
		$this->assertFalse($localized->isPropertyModified('pLStr'));

		$localized->reset();

		$loaded = $localized->getCurrentLocalization();
		$this->assertNotSame($loaded, $localizedI18nPartFr);
		
		$this->assertEquals($localized->getId(), $loaded->getId());
		$this->assertEquals('fr_FR', $loaded->getLCID());
		$this->assertEquals('Localized Label', $loaded->getPLStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $loaded->getPersistentState());

		$localized->delete();
		$this->assertEquals(DocumentManager::STATE_DELETED, $loaded->getPersistentState());

		$deleted = $localized->getCurrentLocalization();
		$this->assertSame($loaded, $deleted);
		$this->getApplicationServices()->getTransactionManager()->commit();
	}


	public function testPropertyDocumentIds()
	{
		$manager = $this->getObject();

		$this->getApplicationServices()->getTransactionManager()->begin();
		/* @var $sd1 \Project\Tests\Documents\Localized */
		$sd1 = $manager->getNewDocumentInstanceByModelName('Project_Tests_Localized');

		/* @var $sd2 \Project\Tests\Documents\Localized */
		$sd2 = $manager->getNewDocumentInstanceByModelName('Project_Tests_Localized');

		/* @var $basic \Project\Tests\Documents\Basic */
		$basic = $manager->getNewDocumentInstanceByModelName('Project_Tests_Basic');


		$manager->assignId($sd1);
		$manager->insertDocument($sd1);

		$manager->assignId($sd2);
		$manager->insertDocument($sd2);

		$basic->getPDocArr()->add($sd1);
		$basic->getPDocArr()->add($sd2);

		$manager->assignId($basic);
		$manager->insertDocument($basic);
		
		$ids = $manager->getPropertyDocumentIds($basic, 'pDocArr');
		$this->assertEquals(array($sd1->getId(), $sd2->getId()), $ids);
		$this->getApplicationServices()->getTransactionManager()->commit();


		/* @var $b2 \Project\Tests\Documents\Basic */
		$b2 = $manager->getDocumentInstance($basic->getId());
		$this->assertNotSame($basic, $b2);
		$ids = $manager->getPropertyDocumentIds($basic, 'pDocArr');
		$this->assertEquals(array($sd1->getId(), $sd2->getId()), $ids);
	}


	/**
	 * Tests for:
	 *  - getLCIDStackSize
	 *  - getLCID
	 *  - pushLCID
	 *  - popLCID
	 */
	public function testLangStack()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$config->addVolatileEntry('Change/I18n/supported-lcids' , null);
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		
		$config->addVolatileEntry('Change/I18n/langs' , null);
		$config->addVolatileEntry('Change/I18n/langs', array('en_US' => 'us'));

		$i18nManger = $this->getApplicationServices()->getI18nManager();
		$manager = new DocumentManager();
		$manager->setApplicationServices($this->getApplicationServices());
		$manager->setDocumentServices($this->getDocumentServices());

		// There is no default value.
		$this->assertEquals(0, $manager->getLCIDStackSize());
		$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());

		// Push/pop supported languages.
		$manager->pushLCID('it_IT');
		$this->assertEquals(1, $manager->getLCIDStackSize());
		$this->assertEquals('it_IT', $manager->getLCID());
		$manager->pushLCID('en_GB');
		$this->assertEquals(2, $manager->getLCIDStackSize());
		$this->assertEquals('en_GB', $manager->getLCID());
		$manager->popLCID();
		$this->assertEquals(1, $manager->getLCIDStackSize());
		$this->assertEquals('it_IT', $manager->getLCID());
		$manager->popLCID();
		$this->assertEquals(0, $manager->getLCIDStackSize());
		$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());

		// Pop from an empty stack.
		try
		{
			$manager->popLCID();
			$this->fail('A LogicException should be thrown.');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals(0, $manager->getLCIDStackSize());
			$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());
		}

		// Push not spported language.
		try
		{
			$manager->pushLCID('kl');
			$this->fail('A InvalidArgumentException should be thrown.');
		}
		catch (\InvalidArgumentException $e)
		{
			$this->assertEquals(0, $manager->getLCIDStackSize());
			$this->assertEquals($i18nManger->getLCID(), $manager->getLCID());
		}
	}

	public function testTransactionLangStack()
	{
		$application = $this->getApplication();
		$config = $application->getConfiguration();
		$config->addVolatileEntry('Change/I18n/supported-lcids' , null);
		$config->addVolatileEntry('Change/I18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));

		$manager = $this->getDocumentServices()->getDocumentManager();

		$manager->pushLCID('fr_FR');
		$manager->pushLCID('en_GB');
		$manager->pushLCID('it_IT');
		$this->getApplicationServices()->getTransactionManager()->begin();
		$manager->pushLCID('es_ES');
		$manager->pushLCID('en_US');
		$this->getApplicationServices()->getTransactionManager()->begin();
		$manager->pushLCID('en_GB');
		$this->assertEquals('en_GB', $manager->getLCID());

		try
		{
			$this->getApplicationServices()->getTransactionManager()->rollBack();
			$this->fail('RollbackException expected');
		}
		catch (\Change\Transaction\RollbackException $e)
		{

		}
		$this->assertEquals('en_US', $manager->getLCID());

		$this->getApplicationServices()->getTransactionManager()->rollBack();
		$this->assertEquals('it_IT', $manager->getLCID());
	}
}