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
		$paths = array();
		$workspace = $application->getWorkspace();
		if (is_dir($workspace->pluginsModulesPath()))
		{
			$pattern = implode(DIRECTORY_SEPARATOR, array($workspace->pluginsModulesPath(), 'Change', '*', 'Documents', 'Assets', '*.xml'));
			$paths = array_merge($paths, \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT));
		}
		
		$nbModels = 0;
		foreach ($paths as $definitionPath)
		{
			$parts = explode(DIRECTORY_SEPARATOR, $definitionPath);
			$count = count($parts);
			$documentName = basename($parts[$count - 1], '.xml');
			$moduleName = $parts[$count - 4];
			$vendor = $parts[$count - 5];
			$compiler->loadDocument($vendor, $moduleName, $documentName, $definitionPath);
			$nbModels++;
		}
		
		$compiler->buildTree();
		$compiler->validateInheritance();
		$compiler->saveModelsPHPCode();
	}
	
	/**
	 * Simulate generate-db-schema command
	 */
	protected function generateDbSchema(\Change\Application $application)
	{
		$dbp = $application->getApplicationServices()->getDbProvider();
		$schemaManager = $dbp->getSchemaManager();
		
		if (!$schemaManager->check())
		{
			throw new \RuntimeException('unable to connect to database:' . $schemaManager->getName());
		}
		$relativePath = 'Db' . DIRECTORY_SEPARATOR . ucfirst($dbp->getType()) . DIRECTORY_SEPARATOR . 'Assets';
		
		$workspace = $application->getWorkspace();
		$pattern = $workspace->changePath($relativePath, '*.sql');
		
		$paths = \Zend\Stdlib\Glob::glob($pattern, \Zend\Stdlib\Glob::GLOB_NOESCAPE + \Zend\Stdlib\Glob::GLOB_NOSORT);
		
		foreach ($paths as $path)
		{
			$sql = file_get_contents($path);
			$schemaManager->execute($sql);
		}

		$documentSchema = new \Compilation\Change\Documents\Schema();
		foreach ($documentSchema->getTables() as $tableDef)
		{
			/* @var $tableDef \Change\Db\Schema\TableDefinition */
			$schemaManager->createOrAlter($tableDef);
		}
		
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
		return \Change\Application::getInstance()->getDocumentServices()->getDocumentManager();
	}
	
	/**
	 * @depends testConstruct
	 * @param \Change\Documents\DocumentManager $manager
	 */
	public function testGetNewDocumentInstance(DocumentManager $manager)
	{
		$document = $manager->getNewDocumentInstanceByModelName('Change_Generic_Folder');
		$this->assertInstanceOf('\Change\Generic\Documents\Folder', $document);
		$this->assertEquals(-1, $document->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document->getPersistentState());
		
		$model = $document->getDocumentModel();
		
		$document2 = $manager->getNewDocumentInstanceByModel($model);
		$this->assertInstanceOf('\Change\Generic\Documents\Folder', $document2);
		$this->assertEquals(-2, $document2->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document2->getPersistentState());
		
		return $manager;
	}
	
	/**
	 * @depends testGetNewDocumentInstance
	 * @param \Change\Documents\DocumentManager $manager
	 */
	public function testAffectId(DocumentManager $manager)
	{
		$document = $manager->getNewDocumentInstanceByModelName('Change_Generic_Folder');
		$this->assertLessThan(0, $document->getId());
		$manager->affectId($document);
		$this->assertGreaterThan(0, $document->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document->getPersistentState());
		
		$definedId = $document->getId() + 10;
		$document2 = $manager->getNewDocumentInstanceByModelName('Change_Generic_Folder');
		$this->assertLessThan(0, $document2->getId());
		$document2->initialize($definedId);
		$manager->affectId($document2);
		$this->assertEquals($definedId, $document2->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document2->getPersistentState());
		
		$document3 = $manager->getNewDocumentInstanceByModelName('Change_Generic_Folder');
		$this->assertLessThan(0, $document3->getId());
		$manager->affectId($document3);
		$this->assertEquals($definedId + 1, $document3->getId());
		$this->assertEquals(DocumentManager::STATE_NEW, $document3->getPersistentState());
		
		return array($manager, $document, $document2, $document3);
	}	
	
	/**
	 * @depends testAffectId
	 */
	public function testInsertDocument($array)
	{
		/* @var $manager \Change\Documents\DocumentManager */
		/* @var $document \Change\Documents\AbstractDocument */
		list($manager, $document, $document2, $document3) = $array;
		$manager->insertDocument($document);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document->getPersistentState());
		
		$manager->insertDocument($document2);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document2->getPersistentState());
		
		$manager->insertDocument($document3);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document3->getPersistentState());
		
		return array($manager, $document);
	}
	
	/**
	 * @depends testInsertDocument
	 */
	public function testUpdateDocument($array)
	{
		/* @var $manager \Change\Documents\DocumentManager */
		/* @var $document \Change\Generic\Documents\Folder */
		list($manager, $document) = $array;
		$document->setLabel('Document Label');
		$this->assertTrue($document->isPropertyModified('label'));
		
		$manager->updateDocument($document);
		$this->assertFalse($document->isPropertyModified('label'));
		
		return $array;
	}
	
	/**
	 * @depends testUpdateDocument
	 */
	public function testGetDocumentInstance($array)
	{
		/* @var $manager \Change\Documents\DocumentManager */
		/* @var $document \Change\Generic\Documents\Folder */
		list($manager, $document) = $array;
		
		$cachedDoc = $manager->getDocumentInstance($document->getId());
		$this->assertTrue($cachedDoc === $document);
		$this->assertEquals(DocumentManager::STATE_LOADED, $cachedDoc->getPersistentState());
		
		$manager->reset();
		$newDoc1 = $manager->getDocumentInstance($document->getId());
		$this->assertTrue($newDoc1 !== $document);
		$this->assertInstanceOf('\Change\Generic\Documents\Folder', $newDoc1);
		$this->assertEquals($document->getId(), $newDoc1->getId());
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $newDoc1->getPersistentState());
		
		return array($manager, $newDoc1);
	}
	
	/**
	 * @depends testGetDocumentInstance
	 */
	public function testLoadDocument($array)
	{
		/* @var $manager \Change\Documents\DocumentManager */
		/* @var $document \Change\Generic\Documents\Folder */
		list($manager, $document) = $array;
	
		$manager->loadDocument($document);
		$this->assertEquals(DocumentManager::STATE_LOADED, $document->getPersistentState());
		$this->assertEquals('Document Label', $document->getLabel());
		
		return $array;
	}
	
	/**
	 * @depends testLoadDocument
	 */	
	public function testMetas($array)
	{
		/* @var $manager \Change\Documents\DocumentManager */
		/* @var $document \Change\Generic\Documents\Folder */
		list($manager, $document) = $array;
		$metas = array('k1' => 'v1', 'k2' => array(45, 46, 50));
		$manager->saveMetas($document, $metas);
		
		$metasLoaded = $manager->loadMetas($document);
		$this->assertEquals($metas, $metasLoaded);
		
		return $array;
	}

	/**
	 * @depends testMetas
	 */	
	public function testRelations($array)
	{
		/* @var $manager \Change\Documents\DocumentManager */
		/* @var $document \Change\Generic\Documents\Folder */
		list($manager, $document) = $array;
		
		$newDoc = $manager->getNewDocumentInstanceByModelName('Change_Generic_Folder');
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
	 * @depends testRelations
	 */
	public function testI18nDocument(DocumentManager $manager)
	{
		$page = $manager->getNewDocumentInstanceByModelName('Change_Website_Page');
		$pageI18nPartFr = $manager->getI18nDocumentInstanceByDocument($page, 'fr_FR');
		
		/* @var $pageI18nPartFr \Compilation\Change\Website\Documents\PageI18n */
		$tmpId = $page->getId();
		$this->assertNotNull($pageI18nPartFr);
		$this->assertEquals($tmpId, $pageI18nPartFr->getId());
		$this->assertEquals('fr_FR', $pageI18nPartFr->getLCID());
		$this->assertEquals(DocumentManager::STATE_NEW, $pageI18nPartFr->getPersistentState());
		
		$manager->affectId($page);
		$manager->insertDocument($page);
		$this->assertEquals(DocumentManager::STATE_LOADED, $page->getPersistentState());
		
		$this->assertNotEquals($tmpId, $page->getId());
		
		$manager->insertI18nDocument($page, $pageI18nPartFr);
		$this->assertEquals($page->getId(), $pageI18nPartFr->getId());
		$this->assertEquals(DocumentManager::STATE_LOADED, $pageI18nPartFr->getPersistentState());
		
		
		$pageI18nPartFr->setLabel('Page Label');
		$this->assertTrue($pageI18nPartFr->isPropertyModified('label'));
		$manager->updateI18nDocument($page, $pageI18nPartFr);
		$this->assertFalse($pageI18nPartFr->isPropertyModified('label'));
		
		$loaded = $manager->getI18nDocumentInstanceByDocument($page, 'fr_FR');
		$this->assertNotSame($loaded, $pageI18nPartFr);
		
		$this->assertEquals($page->getId(), $loaded->getId());
		$this->assertEquals('fr_FR', $loaded->getLCID());
		$this->assertEquals('Page Label', $loaded->getLabel());
		$this->assertEquals(DocumentManager::STATE_LOADED, $loaded->getPersistentState());
		
		return $manager;
	}
	
	/**
	 * @param \Change\Documents\DocumentManager $manager
	 * @depends testI18nDocument
	 */
	public function testPropertyDocumentIds(DocumentManager $manager)
	{
		$g1 = $manager->getNewDocumentInstanceByModelName('Change_Users_Group');
		$g2 = $manager->getNewDocumentInstanceByModelName('Change_Users_Group');
		
		$user = $manager->getNewDocumentInstanceByModelName('Change_Users_User');
		/* @var $user \Change\Users\Documents\User */
		$user->addGroups($g1);
		$user->addGroups($g2);
		
		$manager->affectId($g1);
		$manager->insertDocument($g1);

		$manager->affectId($g2);
		$manager->insertDocument($g2);
		
		$manager->affectId($user);
		$manager->insertDocument($user);
		
		$ids = $manager->getPropertyDocumentIds($user, 'groups');
		$this->assertEquals(array($g1->getId(), $g2->getId()), $ids);
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