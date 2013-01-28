<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\AbstractService;
use Change\Documents\DocumentManager;

class AbstractDocumentTest extends \PHPUnit_Framework_TestCase
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
	
	public static function tearDownAfterClass()
	{
		$dbp = \Change\Application::getInstance()->getApplicationServices()->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
	}
		
	public function testInitializeDB()
	{
		$application = \Change\Application::getInstance();
		$this->compileDocuments($application);
		$this->generateDbSchema($application);
	}
	
	/**
	 * @depends testInitializeDB
	 */
	public function testBasic()
	{
		$testsBasicService = \Change\Application::getInstance()->getDocumentServices()->getProjectTestsBasic();
		$basicDoc = $testsBasicService->getNewDocumentInstance();
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $basicDoc);
		$this->assertEquals('Project_Tests_Basic', $basicDoc->getDocumentModelName());
		
		$this->assertEquals(DocumentManager::STATE_NEW, $basicDoc->getPersistentState());
		$this->assertLessThan(0 , $basicDoc->getId());
		$this->assertTrue($basicDoc->isNew());
		$this->assertFalse($basicDoc->isDeleted());
		$this->assertFalse($basicDoc->hasModifiedProperties());
		$this->assertFalse($basicDoc->hasModifiedMetas());
		$this->assertCount(0, $basicDoc->getPropertiesErrors());
		
		$this->assertNull($basicDoc->getPStr());
		$this->assertNull($basicDoc->getPStrOldValue());
		
		$this->assertInstanceOf('\DateTime', $basicDoc->getCreationDate());
		$this->assertInstanceOf('\DateTime', $basicDoc->getModificationDate());
		
		$this->assertFalse($basicDoc->isValid());
		$this->assertCount(1, $basicDoc->getPropertiesErrors());
		$this->assertArrayHasKey('pStr', $basicDoc->getPropertiesErrors());
		
		$basicDoc->setPStr('string');
		$this->assertEquals('string', $basicDoc->getPStr());
		$this->assertNull($basicDoc->getPStrOldValue());
		
		$basicDoc->setPInt(50);
		$basicDoc->setPFloat(0.03);
		
		$this->assertTrue($basicDoc->isValid());
		$this->assertCount(0, $basicDoc->getPropertiesErrors());
		$this->assertFalse($basicDoc->hasModifiedProperties());
		
		$basicDoc->save();
		$this->assertGreaterThan(0 , $basicDoc->getId());
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicDoc->getPersistentState());
		$this->assertFalse($basicDoc->isNew());
		$this->assertFalse($basicDoc->isDeleted());
		$this->assertFalse($basicDoc->hasModifiedProperties());
		
		$basicDoc->setPStr('string 2');
		$this->assertTrue($basicDoc->hasModifiedProperties());
		$this->assertTrue($basicDoc->isPropertyModified('pStr'));
		$this->assertEquals('string', $basicDoc->getPStrOldValue());
		
		$basicDoc->setPStr('string');
		$this->assertFalse($basicDoc->hasModifiedProperties());
		$this->assertFalse($basicDoc->isPropertyModified('pStr'));
		$this->assertNull($basicDoc->getPStrOldValue());

		$basicDoc->setPStr('string 2');
		$basicDoc->setPDec(8.7);
		$this->assertTrue($basicDoc->hasModifiedProperties());
		$this->assertCount(2, $basicDoc->getModifiedPropertyNames());
		
		$this->assertCount(2, $basicDoc->getOldPropertyValues());
		$this->assertNull($basicDoc->getPDecOldValue());
		$this->assertEquals('string', $basicDoc->getPStrOldValue());
		
		$basicDoc->save();
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicDoc->getPersistentState());
		$this->assertFalse($basicDoc->hasModifiedProperties());
		$this->assertEquals('string 2', $basicDoc->getPStr());
		$this->assertEquals('8.7', $basicDoc->getPDec());
		
		$documentId = $basicDoc->getId();
		$basicDoc->getDocumentManager()->reset();
		
		$basicDoc2 = $testsBasicService->getDocumentInstance($documentId);
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $basicDoc2);
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $basicDoc2->getPersistentState());
		$this->assertNotSame($basicDoc, $basicDoc2);
		
		$this->assertEquals('string 2', $basicDoc2->getPStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicDoc2->getPersistentState());
		
		$basicDoc2->delete();
		$this->assertEquals(DocumentManager::STATE_DELETED, $basicDoc2->getPersistentState());
		$this->assertTrue($basicDoc2->isDeleted());
		
		$datas = $basicDoc2->getDocumentManager()->getBackupData($documentId);
		$this->assertArrayHasKey('pStr', $datas);
		$this->assertEquals('string 2', $datas['pStr']);
		$this->assertArrayHasKey('deletiondate', $datas);
		$this->assertInstanceOf('\DateTime', $datas['deletiondate']);
	}
}