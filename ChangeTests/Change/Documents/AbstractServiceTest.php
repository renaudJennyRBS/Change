<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\AbstractService;
use Change\Documents\DocumentManager;

class AbstractServiceTest extends \PHPUnit_Framework_TestCase
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
//   		$dbp = \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
//   		$dbp->getSchemaManager()->clearDB();
	}
	
	/**
	 * @depends testInitializeDB
	 */
	public function testBasic()
	{
		$testsBasicService = \Change\Application::getInstance()->getDocumentServices()->getTestsBasic();
		$this->assertInstanceOf('\Change\Tests\Documents\BasicService', $testsBasicService);
		$this->assertEquals('Change_Tests_Basic', $testsBasicService->getModelName());
		
		$basicDoc = $testsBasicService->getNewDocumentInstance();
		$this->assertInstanceOf('\Change\Tests\Documents\Basic', $basicDoc);
		
		$basicDoc->setPStr('Basic 1');
		$basicDoc->setCreationDate(null);
		$basicDoc->setModificationDate(null);
		$testsBasicService->create($basicDoc);
		
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicDoc->getPersistentState());
		$this->assertNotNull($basicDoc->getCreationDate());
		$this->assertNotNull($basicDoc->getModificationDate());
		
		$basicDoc->setPStr('Basic 2');
		$basicDoc->setModificationDate(null);
		$this->assertTrue($basicDoc->isPropertyModified('pStr'));
		$this->assertTrue($basicDoc->isPropertyModified('modificationDate'));
		
		$testsBasicService->update($basicDoc);
		$this->assertFalse($basicDoc->isPropertyModified('pStr'));
		$this->assertFalse($basicDoc->isPropertyModified('modificationDate'));
		$this->assertNotNull($basicDoc->getModificationDate());
		
		$basicDoc->getDocumentManager()->reset();
		
		$basicDoc2 = $testsBasicService->getDocumentInstance($basicDoc->getId());
		$this->assertInstanceOf('\Change\Tests\Documents\Basic', $basicDoc2);
		$this->assertNotSame($basicDoc, $basicDoc2);
		$this->assertEquals($basicDoc2->getId(), $basicDoc->getId());
		
		try
		{
			$testsBasicService->create($basicDoc2);
			$this->fail('Document is not new');
		} 
		catch (\LogicException $e) 
		{
			$this->assertEquals('Document is not new', $e->getMessage()) ;
		}
		
		try
		{
			$basicDoc2->setCreationDate(Null);
			$testsBasicService->update($basicDoc2);
			$this->fail('Document is not valid');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Document is not valid', $e->getMessage());
			$this->assertArrayHasKey('creationDate', $basicDoc2->getPropertiesErrors());
		}
		
		$backup = $testsBasicService->generateBackupData($basicDoc2);
		$this->assertArrayHasKey('metas', $backup);
		$this->assertArrayHasKey('id', $backup);
		$this->assertArrayHasKey('model', $backup);
		$this->assertArrayHasKey('pStr', $backup);
		$this->assertEquals('Basic 2', $backup['pStr']);
		
		$testsBasicService->delete($basicDoc2);
		$this->assertEquals(DocumentManager::STATE_DELETED, $basicDoc2->getPersistentState());
		
		
	}
	
	
	/**
	 * @depends testBasic
	 */
	public function testLocalized()
	{
		$dm = \Change\Application::getInstance()->getDocumentServices()->getDocumentManager();
		
		$dm->pushLCID('fr_FR');
		$testsLocalizedService = \Change\Application::getInstance()->getDocumentServices()->getTestsLocalized();
		
		$this->assertInstanceOf('\Change\Tests\Documents\LocalizedService', $testsLocalizedService);
		$this->assertEquals('Change_Tests_Localized', $testsLocalizedService->getModelName());
	
		$localizedDoc = $testsLocalizedService->getNewDocumentInstance();
		$this->assertInstanceOf('\Change\Tests\Documents\Localized', $localizedDoc);
	
		$localizedDoc->setPStr('Basic 1');
		$localizedDoc->setPLStr('Localized 1 FR');
		$localizedDoc->setCreationDate(null);
		$localizedDoc->setModificationDate(null);
		$testsLocalizedService->create($localizedDoc);
	
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getPersistentState());
		$this->assertNotNull($localizedDoc->getCreationDate());
		$this->assertNotNull($localizedDoc->getModificationDate());
	
		$localizedDoc->setPStr('Basic 2');
		$localizedDoc->setPLStr('Localized 2 FR');
		$localizedDoc->setModificationDate(null);
		$this->assertTrue($localizedDoc->isPropertyModified('pStr'));
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$this->assertTrue($localizedDoc->isPropertyModified('modificationDate'));
	
		$testsLocalizedService->update($localizedDoc);
		$this->assertFalse($localizedDoc->isPropertyModified('pStr'));
		$this->assertFalse($localizedDoc->isPropertyModified('pLStr'));
		$this->assertFalse($localizedDoc->isPropertyModified('modificationDate'));
		$this->assertNotNull($localizedDoc->getModificationDate());
	
		$dm->reset();
	
		$basicDoc2 = $testsLocalizedService->getDocumentInstance($localizedDoc->getId());
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $basicDoc2->getPersistentState());
		
		$this->assertInstanceOf('\Change\Tests\Documents\Localized', $basicDoc2);
		$this->assertNotSame($localizedDoc, $basicDoc2);
		$this->assertEquals($basicDoc2->getId(), $localizedDoc->getId());
		$this->assertCount(1, $basicDoc2->getLCIDArray());
		
		$basicI18nFR = $basicDoc2->getCurrentI18nPart();
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicI18nFR->getPersistentState());
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $basicDoc2->getPersistentState());
		
		$this->assertEquals('Basic 2', $basicDoc2->getPStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicDoc2->getPersistentState());
	
		try
		{
			$testsLocalizedService->create($basicDoc2);
			$this->fail('Document is not new');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Document is not new', $e->getMessage()) ;
		}
	
		try
		{
			$basicDoc2->setCreationDate(Null);
			$testsLocalizedService->update($basicDoc2);
			$this->fail('Document is not valid');
		}
		catch (\LogicException $e)
		{
			$this->assertEquals('Document is not valid', $e->getMessage());
			$this->assertArrayHasKey('creationDate', $basicDoc2->getPropertiesErrors());
		}
		
		$dm->popLCID();
		
		$dm->pushLCID('en_GB');
		$basicDoc2->setPLStr('Localized 1 GB');
		$basicI18nGB = $basicDoc2->getCurrentI18nPart();
		$this->assertEquals(DocumentManager::STATE_NEW, $basicI18nGB->getPersistentState());
		
		$testsLocalizedService->create($basicDoc2);	
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicI18nGB->getPersistentState());
		$dm->popLCID();

		$this->assertCount(2, $basicDoc2->getLCIDArray());
	
		$backup = $testsLocalizedService->generateBackupData($basicDoc2);
		$this->assertArrayHasKey('metas', $backup);
		$this->assertArrayHasKey('id', $backup);
		$this->assertArrayHasKey('model', $backup);
		$this->assertArrayHasKey('pStr', $backup);
		$this->assertEquals('Basic 2', $backup['pStr']);
		$this->assertArrayHasKey('LCID', $backup);
		$this->assertArrayHasKey('fr_FR', $backup['LCID']);
		$this->assertEquals('Localized 2 FR', $backup['LCID']['fr_FR']['pLStr']);
		
		$this->assertArrayHasKey('en_GB', $backup['LCID']);
		$this->assertEquals('Localized 1 GB', $backup['LCID']['en_GB']['pLStr']);	
		
		$testsLocalizedService->delete($basicDoc2);
		$this->assertEquals(DocumentManager::STATE_DELETED, $basicDoc2->getPersistentState());
		$this->assertEquals(DocumentManager::STATE_DELETED, $basicI18nGB->getPersistentState());
		$this->assertEquals(DocumentManager::STATE_DELETED, $basicI18nFR->getPersistentState());
		$this->assertCount(0, $basicDoc2->getLCIDArray());
	}
	
}