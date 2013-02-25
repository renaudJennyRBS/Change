<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Publishable;
use Change\Documents\Correction;

class AbstractDocumentTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function tearDownAfterClass()
	{
		$dbp =  static::getNewApplicationServices(static::getNewApplication())->getDbProvider();
		$dbp->getSchemaManager()->clearDB();
	}
		
	public function testInitializeDB()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$compiler->generate();

		$generator = new \Change\Db\Schema\Generator($this->getApplication()->getWorkspace(), $this->getApplicationServices()->getDbProvider());
		$generator->generate();
	}

	/**
	 * @depends testInitializeDB
	 */
	public function testSerialize()
	{
		$testsBasicService = $this->getDocumentServices()->getProjectTestsBasic();
		$basicDoc = $testsBasicService->getNewDocumentInstance();
		$str = serialize($basicDoc);
		$this->assertEquals(serialize(null), $str);
	}

	/**
	 * @depends testSerialize
	 */
	public function testBasic()
	{
		/* @var $testsBasicService \Project\Tests\Documents\BasicService */
		$testsBasicService = $this->getDocumentServices()->getProjectTestsBasic();
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

		$this->getDocumentServices()->getDocumentManager()->reset();
	}


	/**
	 * @depends testBasic
	 */
	public function testLocalized()
	{
		/* @var $testsLocalizedService \Project\Tests\Documents\LocalizedService */
		$testsLocalizedService = $this->getDocumentServices()->getProjectTestsLocalized();
		$dm = $this->getDocumentServices()->getDocumentManager();

		$localizedDoc = $testsLocalizedService->getNewDocumentInstance();
		$dm->pushLCID('fr_FR');

		$this->assertInstanceOf('\Project\Tests\Documents\Localized', $localizedDoc);
		$this->assertEquals('Project_Tests_Localized', $localizedDoc->getDocumentModelName());

		$this->assertEquals(DocumentManager::STATE_NEW, $localizedDoc->getPersistentState());
		$this->assertLessThan(0 , $localizedDoc->getId());
		$this->assertTrue($localizedDoc->isNew());
		$this->assertFalse($localizedDoc->isDeleted());
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertFalse($localizedDoc->hasModifiedMetas());
		$this->assertCount(0, $localizedDoc->getPropertiesErrors());

		$this->assertEquals('fr_FR', $localizedDoc->getLCID());
		$this->assertNull($localizedDoc->getRefLCID());

		$this->assertNull($localizedDoc->getPStr());
		$this->assertNull($localizedDoc->getPStrOldValue());

		$this->assertNull($localizedDoc->getPLStr());
		$this->assertNull($localizedDoc->getPLStrOldValue());

		$this->assertInstanceOf('\DateTime', $localizedDoc->getCreationDate());
		$this->assertInstanceOf('\DateTime', $localizedDoc->getModificationDate());

		$this->assertFalse($localizedDoc->isValid());

		$this->assertCount(3, $localizedDoc->getPropertiesErrors());
		$this->assertArrayHasKey('pStr', $localizedDoc->getPropertiesErrors());
		$this->assertArrayHasKey('pLStr', $localizedDoc->getPropertiesErrors());
		$this->assertArrayHasKey('refLCID', $localizedDoc->getPropertiesErrors());

		$localizedDoc->setPStr('string');
		$this->assertEquals('string', $localizedDoc->getPStr());
		$this->assertNull($localizedDoc->getPStrOldValue());

		$localizedDoc->setPLStr('string FR');
		$this->assertEquals('string FR', $localizedDoc->getPLStr());
		$this->assertNull($localizedDoc->getPLStrOldValue());

		$localizedDoc->setRefLCID('fr_FR');
		$localizedDoc->setPInt(50);
		$localizedDoc->setPFloat(0.03);

		$this->assertTrue($localizedDoc->isValid());
		$this->assertCount(0, $localizedDoc->getPropertiesErrors());
		$this->assertFalse($localizedDoc->hasModifiedProperties());

		$localizedDoc->save();

		$this->assertGreaterThan(0 , $localizedDoc->getId());
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getPersistentState());
		$this->assertFalse($localizedDoc->isNew());
		$this->assertFalse($localizedDoc->isDeleted());
		$this->assertFalse($localizedDoc->hasModifiedProperties());

		$localizedDoc->setPLStr('string FR 2');

		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$this->assertEquals('string FR', $localizedDoc->getPLStrOldValue());

		$localizedDoc->setPLStr('string FR');
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertFalse($localizedDoc->isPropertyModified('pLStr'));
		$this->assertNull($localizedDoc->getPLStrOldValue());

		$localizedDoc->setPLStr('string FR 2');
		$localizedDoc->setPLDec(8.7);
		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertCount(2, $localizedDoc->getModifiedPropertyNames());

		$this->assertNull($localizedDoc->getPLDecOldValue());
		$this->assertEquals('string FR', $localizedDoc->getPLStrOldValue());

		$localizedDoc->save();
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getPersistentState());
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertEquals('string FR 2', $localizedDoc->getPLStr());
		$this->assertEquals('8.7', $localizedDoc->getPLDec());

		$documentId = $localizedDoc->getId();

		$dm->popLCID();


		$dm->pushLCID('en_GB');
		$this->assertEquals('en_GB', $localizedDoc->getLCID());
		$this->assertEquals(DocumentManager::STATE_NEW, $localizedDoc->getCurrentLocalizedPart()->getPersistentState());

		$this->assertNull($localizedDoc->getPLStr());
		$localizedDoc->setPLStr('string EN');

		$localizedDoc->create();

		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalizedPart()->getPersistentState());

		$dm->popLCID();

		$dm->pushLCID('fr_FR');
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalizedPart()->getPersistentState());
		$this->assertEquals('fr_FR', $localizedDoc->getLCID());
		$this->assertEquals('string FR 2', $localizedDoc->getPLStr());
		$dm->popLCID();

		$dm->pushLCID('en_GB');
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalizedPart()->getPersistentState());
		$this->assertEquals('en_GB', $localizedDoc->getLCID());
		$this->assertEquals('string EN', $localizedDoc->getPLStr());
		$dm->popLCID();

		$dm->reset();

		$dm->pushLCID('en_GB');
		$localizedDoc2 = $testsLocalizedService->getDocumentInstance($documentId);
		$this->assertInstanceOf('\Project\Tests\Documents\Localized', $localizedDoc2);
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $localizedDoc2->getPersistentState());
		$this->assertNotSame($localizedDoc, $localizedDoc2);

		$this->assertEquals('string', $localizedDoc2->getPStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc2->getPersistentState());

		$localizedDoc2->delete();
		$this->assertEquals(DocumentManager::STATE_DELETED, $localizedDoc2->getPersistentState());
		$this->assertTrue($localizedDoc2->isDeleted());

		$datas = $localizedDoc2->getDocumentManager()->getBackupData($documentId);

		$this->assertArrayHasKey('pStr', $datas);
		$this->assertEquals('string', $datas['pStr']);
		$this->assertArrayHasKey('deletiondate', $datas);
		$this->assertInstanceOf('\DateTime', $datas['deletiondate']);

		$this->assertArrayHasKey('LCID', $datas);
		$this->assertEquals('string FR 2', $datas['LCID']['fr_FR']['pLStr']);
		$this->assertEquals('string EN', $datas['LCID']['en_GB']['pLStr']);
		$dm->popLCID();


		$this->getDocumentServices()->getDocumentManager()->reset();
	}

	/**
	 * @depends testLocalized
	 */
	public function testCorrection()
	{
		/* @var $testsCorrectionService \Project\Tests\Documents\CorrectionService */
		$testsCorrectionService = $this->getDocumentServices()->getProjectTestsCorrection();
		$dm = $this->getDocumentServices()->getDocumentManager();

		$c1 = $testsCorrectionService->getNewDocumentInstance();

		$c1->setLabel('c1');
		$c1->setPublicationStatus(Publishable::STATUS_DRAFT);
		$c1->setStr1('Str1');
		$c1->setStr2('Str2');
		$c1->setStr3('Str3');
		$c1->setStr4('Str4');
		$c1->create();
		$this->assertFalse($c1->hasCorrection());

		$c1Id = $c1->getId();
		$this->assertGreaterThan(0, $c1Id);
		$this->assertEquals(DocumentManager::STATE_LOADED, $c1->getPersistentState());
		$this->assertEquals(DocumentManager::STATE_LOADED, $c1->getCurrentLocalizedPart()->getPersistentState());

		$c1->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$this->assertTrue($c1->isPropertyModified('publicationStatus'));
		$c1->update();
		$this->assertFalse($c1->hasCorrection());

		$c1->setStr1('Str1 v2');
		$c1->setStr2('Str2 v2');
		$c1->setStr3('Str3 v2');
		$c1->setStr4('Str4 v2');

		$this->assertTrue($c1->hasModifiedProperties());
		$c1->update();

		$this->assertFalse($c1->hasModifiedProperties());
		$this->assertTrue($c1->hasCorrection());
		$this->assertEquals('Str1 v2', $c1->getStr1());
		$this->assertEquals('Str2 v2', $c1->getStr2());
		$this->assertEquals('Str3 v2', $c1->getStr3());
		$this->assertEquals('Str4 v2', $c1->getStr4());

		$c1->reset();
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $c1->getPersistentState());
		$this->assertEquals('Str1 v2', $c1->getStr1());
		$this->assertEquals('Str2', $c1->getStr2());
		$this->assertEquals('Str3 v2', $c1->getStr3());
		$this->assertEquals('Str4', $c1->getStr4());
		$this->assertTrue($c1->hasCorrection());

		$corr = $c1->getCorrection();
		$this->assertEquals('Str2 v2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4 v2', $corr->getPropertyValue('str4'));
		$this->assertEquals(Correction::STATUS_DRAFT, $corr->getStatus());

		$corr->setStatus(Correction::STATUS_PUBLISHABLE);
		$dm->saveCorrection($corr);

		$c1->getDocumentService()->applyCorrection($c1, $corr);

		$this->assertEquals(Correction::STATUS_FILED, $corr->getStatus());
		$this->assertEquals('Str2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4', $corr->getPropertyValue('str4'));

		$this->assertEquals('Str2 v2', $c1->getStr2());
		$this->assertEquals('Str4 v2', $c1->getStr4());

		$this->getDocumentServices()->getDocumentManager()->reset();
	}
}