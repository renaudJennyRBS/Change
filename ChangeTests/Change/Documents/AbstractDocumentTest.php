<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Publishable;
use Change\Documents\Correction;

class AbstractDocumentTest extends \ChangeTests\Change\TestAssets\TestCase
{

	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}


	public static function tearDownAfterClass()
	{
		static::clearDB();
	}


	public function testSerialize()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$str = serialize($basicDoc);
		$this->assertEquals(serialize(null), $str);
	}


	public function testBasic()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $basicDoc);
		$this->assertEquals('Project_Tests_Basic', $basicDoc->getDocumentModelName());
		
		$this->assertEquals(DocumentManager::STATE_NEW, $basicDoc->getPersistentState());
		$this->assertLessThan(0 , $basicDoc->getId());
		$this->assertTrue($basicDoc->isNew());
		$this->assertFalse($basicDoc->isDeleted());
		$this->assertFalse($basicDoc->hasModifiedProperties());
		$this->assertFalse($basicDoc->hasModifiedMetas());

		
		$this->assertNull($basicDoc->getPStr());
		$this->assertNull($basicDoc->getPStrOldValue());
		
		$this->assertInstanceOf('\DateTime', $basicDoc->getCreationDate());
		$this->assertInstanceOf('\DateTime', $basicDoc->getModificationDate());

		$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_CREATE, $basicDoc);
		$validation = new \Change\Documents\Events\ValidateListener();
		$validation->onValidate($event);
		$errors = $event->getParam('propertiesErrors');

		$this->assertCount(1, $errors);
		$this->assertArrayHasKey('pStr', $errors);
		
		$basicDoc->setPStr('string');
		$this->assertEquals('string', $basicDoc->getPStr());
		$this->assertNull($basicDoc->getPStrOldValue());
		
		$basicDoc->setPInt(50);
		$basicDoc->setPFloat(0.03);

		$validation->onValidate($event);
		$errors = $event->getParam('propertiesErrors');
		$this->assertNull($errors);
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
		$this->getDocumentServices()->getDocumentManager()->reset();

		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc2 = $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);

		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $basicDoc2);
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $basicDoc2->getPersistentState());
		$this->assertNotSame($basicDoc, $basicDoc2);
		
		$this->assertEquals('string 2', $basicDoc2->getPStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $basicDoc2->getPersistentState());
		
		$basicDoc2->delete();
		$this->assertEquals(DocumentManager::STATE_DELETED, $basicDoc2->getPersistentState());
		$this->assertTrue($basicDoc2->isDeleted());
		
		$datas = $this->getDocumentServices()->getDocumentManager()->getBackupData($documentId);
		$this->assertArrayHasKey('pStr', $datas);
		$this->assertEquals('string 2', $datas['pStr']);
		$this->assertArrayHasKey('deletiondate', $datas);
		$this->assertInstanceOf('\DateTime', $datas['deletiondate']);

		$this->getDocumentServices()->getDocumentManager()->reset();
	}


	public function testLocalized()
	{

		$dm = $this->getDocumentServices()->getDocumentManager();

		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$localizedDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');

		$dm->pushLCID('fr_FR');

		$this->assertInstanceOf('\Project\Tests\Documents\Localized', $localizedDoc);
		$this->assertEquals('Project_Tests_Localized', $localizedDoc->getDocumentModelName());

		$this->assertEquals(DocumentManager::STATE_NEW, $localizedDoc->getPersistentState());
		$this->assertLessThan(0 , $localizedDoc->getId());
		$this->assertTrue($localizedDoc->isNew());
		$this->assertFalse($localizedDoc->isDeleted());
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertFalse($localizedDoc->hasModifiedMetas());


		$this->assertEquals('fr_FR', $localizedDoc->getLCID());
		$this->assertNull($localizedDoc->getRefLCID());

		$this->assertNull($localizedDoc->getPStr());
		$this->assertNull($localizedDoc->getPStrOldValue());

		$this->assertNull($localizedDoc->getPLStr());
		$this->assertNull($localizedDoc->getPLStrOldValue());

		$this->assertInstanceOf('\DateTime', $localizedDoc->getCreationDate());
		$this->assertInstanceOf('\DateTime', $localizedDoc->getModificationDate());


		$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_CREATE, $localizedDoc);
		$validation = new \Change\Documents\Events\ValidateListener();
		$validation->onValidate($event);
		$errors = $event->getParam('propertiesErrors');

		$this->assertCount(2, $errors);
		$this->assertArrayHasKey('pStr', $errors);
		$this->assertArrayHasKey('pLStr', $errors);

		$localizedDoc->setPStr('string');
		$this->assertEquals('string', $localizedDoc->getPStr());
		$this->assertNull($localizedDoc->getPStrOldValue());

		$localizedDoc->setPLStr('string FR');
		$this->assertEquals('string FR', $localizedDoc->getPLStr());
		$this->assertNull($localizedDoc->getPLStrOldValue());

		$localizedDoc->setRefLCID('fr_FR');
		$localizedDoc->setPInt(50);
		$localizedDoc->setPFloat(0.03);

		$validation->onValidate($event);
		$errors = $event->getParam('propertiesErrors');

		$this->assertNull($errors);
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


		$dm->pushLCID('en_US');
		$this->assertEquals('en_US', $localizedDoc->getLCID());
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

		$dm->pushLCID('en_US');
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalizedPart()->getPersistentState());
		$this->assertEquals('en_US', $localizedDoc->getLCID());
		$this->assertEquals('string EN', $localizedDoc->getPLStr());
		$dm->popLCID();

		$dm->reset();

		$dm->pushLCID('en_US');
		$localizedDoc2 = $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		$this->assertInstanceOf('\Project\Tests\Documents\Localized', $localizedDoc2);
		$this->assertEquals(DocumentManager::STATE_INITIALIZED, $localizedDoc2->getPersistentState());
		$this->assertNotSame($localizedDoc, $localizedDoc2);

		$this->assertEquals('string', $localizedDoc2->getPStr());
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc2->getPersistentState());

		$localizedDoc2->delete();
		$this->assertEquals(DocumentManager::STATE_DELETED, $localizedDoc2->getPersistentState());
		$this->assertTrue($localizedDoc2->isDeleted());

		$datas = $this->getDocumentServices()->getDocumentManager()->getBackupData($documentId);

		$this->assertArrayHasKey('pStr', $datas);
		$this->assertEquals('string', $datas['pStr']);
		$this->assertArrayHasKey('deletiondate', $datas);
		$this->assertInstanceOf('\DateTime', $datas['deletiondate']);

		$this->assertArrayHasKey('LCID', $datas);
		$this->assertEquals('string FR 2', $datas['LCID']['fr_FR']['pLStr']);
		$this->assertEquals('string EN', $datas['LCID']['en_US']['pLStr']);
		$dm->popLCID();


		$this->getDocumentServices()->getDocumentManager()->reset();
	}

	public function testCorrection()
	{
		/* @var $c1 \Project\Tests\Documents\Correction */
		$c1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Correction');

		$c1->setLabel('c1');
		$c1->setPublicationStatus(Publishable::STATUS_DRAFT);
		$c1->setStr1('Str1');
		$c1->setStr2('Str2');
		$c1->setStr3('Str3');
		$c1->setStr4('Str4');
		$c1->create();
		$this->assertFalse($c1->getCorrectionFunctions()->hasCorrection());

		$c1Id = $c1->getId();
		$this->assertGreaterThan(0, $c1Id);
		$this->assertEquals(DocumentManager::STATE_LOADED, $c1->getPersistentState());
		$this->assertEquals(DocumentManager::STATE_LOADED, $c1->getCurrentLocalizedPart()->getPersistentState());

		$c1->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$this->assertTrue($c1->isPropertyModified('publicationStatus'));
		$c1->update();
		$this->assertFalse($c1->getCorrectionFunctions()->hasCorrection());

		$c1->setStr1('Str1 v2');
		$c1->setStr2('Str2 v2');
		$c1->setStr3('Str3 v2');
		$c1->setStr4('Str4 v2');

		$this->assertTrue($c1->hasModifiedProperties());
		$c1->update();

		$this->assertFalse($c1->hasModifiedProperties());
		$this->assertTrue($c1->getCorrectionFunctions()->hasCorrection());
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
		$this->assertTrue($c1->getCorrectionFunctions()->hasCorrection());

		$corr = $c1->getCorrectionFunctions()->getCorrection();
		$this->assertEquals('Str2 v2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4 v2', $corr->getPropertyValue('str4'));
		$this->assertEquals(Correction::STATUS_DRAFT, $corr->getStatus());

		$corr->setStatus(Correction::STATUS_PUBLISHABLE);
		$c1->getCorrectionFunctions()->save($corr);

		$c1->getCorrectionFunctions()->publish();

		$this->assertEquals(Correction::STATUS_FILED, $corr->getStatus());
		$this->assertEquals('Str2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4', $corr->getPropertyValue('str4'));

		$this->assertEquals('Str2 v2', $c1->getStr2());
		$this->assertEquals('Str4 v2', $c1->getStr4());
		$this->assertFalse($c1->getCorrectionFunctions()->hasCorrection());

		$this->getDocumentServices()->getDocumentManager()->reset();
	}
}