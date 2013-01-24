<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\DocumentManager;

class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Simulate compile-document command
	 */
	protected function compileDocuments(\Change\Application $application)
	{
		$compiler = new \Change\Documents\Generators\Compiler($application);
		$compiler->generate();
	}
	
	/**
	 * Simulate generate-db-schema command
	 */
	protected function generateDbSchema(\Change\Application $application)
	{
		$generator = new \Change\Db\Schema\Generator($application->getWorkspace(), $application->getApplicationServices()->getDbProvider());
		$generator->generate();
	}
	
	public function testInitializeDB()
	{
		$application = \Change\Application::getInstance();
		$this->compileDocuments($application);
		$this->generateDbSchema($application);
	}
	
	public static function tearDownAfterClass()
	{
   		$dbp = \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
   		$dbp->getSchemaManager()->clearDB();
	}
	
	/**
	 * @depends testInitializeDB
	 * @return \Change\Documents\DocumentManager
	 */
	public function testConstruct()
	{
		$manager = \Change\Application::getInstance()->getDocumentServices()->getDocumentManager();
		$manager->reset();
		return $manager;
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Documents\DocumentManager $manager
	 */
	public function testGetNewDocumentInstance(DocumentManager $manager)
	{
		$document = $manager->getNewDocumentInstanceByModelName('Change_Tests_Basic');
		$this->assertInstanceOf('\Change\Tests\Documents\Basic', $document);
		$this->assertEquals(-1, $document->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document->getPersistentState());
		
		$model = $document->getDocumentModel();
		
		$document2 = $manager->getNewDocumentInstanceByModel($model);
		$this->assertInstanceOf('\Change\Tests\Documents\Basic', $document2);
		$this->assertEquals(-2, $document2->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document2->getPersistentState());
		
		return $manager;
	}
	
	/**
	 * @depends testGetNewDocumentInstance
	 * @param \Change\Documents\DocumentManager $manager
	 */
	public function testDocument(DocumentManager $manager)
	{
		$document = $manager->getNewDocumentInstanceByModelName('Change_Tests_Basic');
		$this->assertLessThan(0, $document->getId());
		$manager->affectId($document);
		$this->assertGreaterThan(0, $document->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document->getPersistentState());
		
		$definedId = $document->getId() + 10;
		$document2 = $manager->getNewDocumentInstanceByModelName('Change_Tests_Basic');
		$this->assertLessThan(0, $document2->getId());
		$document2->initialize($definedId);
		$manager->affectId($document2);
		$this->assertEquals($definedId, $document2->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document2->getPersistentState());
		
		$document3 = $manager->getNewDocumentInstanceByModelName('Change_Tests_Basic');
		$this->assertLessThan(0, $document3->getId());
		$manager->affectId($document3);
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
		$newDoc1 = $manager->getDocumentInstance($document->getId());
		$this->assertTrue($newDoc1 !== $document);
		$this->assertInstanceOf('\Change\Tests\Documents\Basic', $newDoc1);
		$this->assertEquals($document->getId(), $newDoc1->getId());
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $newDoc1->getPersistentState());
			
		$manager->loadDocument($newDoc1);
		$this->assertEquals(DocumentManager::STATE_LOADED, $newDoc1->getPersistentState());
		$this->assertEquals('Document Label', $newDoc1->getPStr());
		
		$metas = array('k1' => 'v1', 'k2' => array(45, 46, 50));
		$manager->saveMetas($newDoc1, $metas);
		
		$metasLoaded = $manager->loadMetas($newDoc1);
		$this->assertEquals($metas, $metasLoaded);
				
		$newDoc = $manager->getNewDocumentInstanceByModelName('Change_Tests_Basic');
		$tmpId = $newDoc->getId();
		$manager->initializeRelationDocumentId($newDoc);
		
		$this->assertSame($newDoc, $manager->getDocumentInstance($tmpId));

		$this->assertEquals($tmpId, $manager->resolveRelationDocumentId($tmpId));
		try 
		{
			$manager->resolveRelationDocumentId(-5000);
			$this->fail('Cached document -5000 not found');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Cached document -5000 not found', $e->getMessage());
		}
		
		$this->assertEquals(1, $manager->resolveRelationDocumentId(1));
		
		$this->assertSame($newDoc, $manager->getRelationDocument($tmpId));
		try
		{
			$manager->getRelationDocument(-5000);
			$this->fail('Cached document -5000 not found');
		}
		catch (\RuntimeException $e)
		{
			$this->assertEquals('Cached document -5000 not found', $e->getMessage());
		}
		
		$this->assertNull($manager->getRelationDocument(1));
		
		$manager->affectId($newDoc);
		
		$finalId = $newDoc->getId();
		$this->assertNotEquals($tmpId, $finalId);
		$this->assertEquals($finalId, $manager->resolveRelationDocumentId($tmpId));
		$this->assertSame($newDoc, $manager->getRelationDocument($tmpId));
		
		return $manager;
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @depends testDocument
	 */
	public function testI18nDocument(DocumentManager $manager)
	{
		$localized = $manager->getNewDocumentInstanceByModelName('Change_Tests_Localized');
		$localizedI18nPartFr = $manager->getI18nDocumentInstanceByDocument($localized, 'fr_FR');
		
		/* @var $localizedI18nPartFr \Compilation\Change\Tests\Documents\LocalizedI18n */
		$tmpId = $localized->getId();
		$this->assertNotNull($localizedI18nPartFr);
		$this->assertEquals($tmpId, $localizedI18nPartFr->getId());
		$this->assertEquals('fr_FR', $localizedI18nPartFr->getLCID());
		$this->assertEquals(DocumentManager::STATE_NEW, $localizedI18nPartFr->getPersistentState());
		
		$manager->affectId($localized);
		$manager->insertDocument($localized);
		$this->assertEquals(DocumentManager::STATE_LOADED, $localized->getPersistentState());
		
		$this->assertNotEquals($tmpId, $localized->getId());
		
		$manager->insertI18nDocument($localized, $localizedI18nPartFr);
		$this->assertEquals($localized->getId(), $localizedI18nPartFr->getId());
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedI18nPartFr->getPersistentState());
		
		$localizedI18nPartFr->setPLStr('Localized Label');
		$this->assertTrue($localizedI18nPartFr->isPropertyModified('pLStr'));
		$manager->updateI18nDocument($localized, $localizedI18nPartFr);
		$this->assertFalse($localizedI18nPartFr->isPropertyModified('pLStr'));
		
		$loaded = $manager->getI18nDocumentInstanceByDocument($localized, 'fr_FR');
		$this->assertNotSame($loaded, $localizedI18nPartFr);
		
		$this->assertEquals($localized->getId(), $loaded->getId());
		$this->assertEquals('fr_FR', $loaded->getLCID());
		$this->assertEquals('Localized Label', $loaded->getPLStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $loaded->getPersistentState());
		
		$manager->deleteDocument($localized);
		$manager->deleteI18nDocuments($localized);
		
		$deleted = $manager->getI18nDocumentInstanceByDocument($localized, 'fr_FR');
		$this->assertEquals(DocumentManager::STATE_DELETED, $deleted->getPersistentState());

		return $manager;
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @depends testI18nDocument
	 */
	public function testPropertyDocumentIds(DocumentManager $manager)
	{
		$sd1 = $manager->getNewDocumentInstanceByModelName('Change_Tests_Localized');
		$sd2 = $manager->getNewDocumentInstanceByModelName('Change_Tests_Localized');
		
		$basic = $manager->getNewDocumentInstanceByModelName('Change_Tests_Basic');
		/* @var $basic \Change\Tests\Documents\Basic */
		$basic->addPDocArr($sd1);
		$basic->addPDocArr($sd2);
		
		$manager->affectId($sd1);
		$manager->insertDocument($sd1);

		$manager->affectId($sd2);
		$manager->insertDocument($sd2);
		
		$manager->affectId($basic);
		$manager->insertDocument($basic);
		
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
		$application = \Change\Application::getInstance();
		$config = $application->getApplicationServices()->getConfiguration();
		$config->addVolatileEntry('i18n/supported-lcids' , null);
		$config->addVolatileEntry('i18n/supported-lcids', array('fr_FR','en_GB','it_IT','es_ES','en_US'));
		
		$config->addVolatileEntry('i18n/langs' , null);
		$config->addVolatileEntry('i18n/langs', array('en_US' => 'us'));
		
		$i18nManger = new \Change\I18n\I18nManager($application);		
		$application->getApplicationServices()->instanceManager()->addSharedInstance($i18nManger, 'Change\I18n\I18nManager');
		$manager = new DocumentManager($application->getApplicationServices(), $application->getDocumentServices());
		
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
}