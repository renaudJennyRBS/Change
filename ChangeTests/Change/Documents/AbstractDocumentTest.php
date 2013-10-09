<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\AbstractDocument;
use Change\Documents\Events\Event;
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

		$eventCollection = new \ArrayObject();
		$callBack = function (Event $event) use ($eventCollection) {
			$eventCollection[] = $event->getName();
		};
		$basicDoc->getEventManager()->attach("*", $callBack);
		
		$this->assertEquals(AbstractDocument::STATE_NEW, $basicDoc->getPersistentState());
		$this->assertLessThan(0 , $basicDoc->getId());
		$this->assertTrue($basicDoc->isNew());
		$this->assertFalse($basicDoc->isDeleted());
		$this->assertTrue($basicDoc->hasModifiedProperties());
		$this->assertEquals(array('creationDate', 'modificationDate'), $basicDoc->getModifiedPropertyNames());
		$this->assertFalse($basicDoc->hasModifiedMetas());

		$this->assertNull($basicDoc->getPStr());
		$this->assertNull($basicDoc->getPStrOldValue());
		
		$this->assertInstanceOf('\DateTime', $basicDoc->getCreationDate());
		$this->assertInstanceOf('\DateTime', $basicDoc->getModificationDate());

		$event = new Event(Event::EVENT_CREATE, $basicDoc);
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
		$this->assertTrue($basicDoc->hasModifiedProperties());
		$this->assertEquals(array('creationDate', 'modificationDate', 'pStr', 'pInt', 'pFloat'), $basicDoc->getModifiedPropertyNames());

		$basicDoc->save();
		$this->assertEquals(array(Event::EVENT_CREATE, Event::EVENT_CREATED), $eventCollection->getArrayCopy());
		$eventCollection->exchangeArray(array());


		$this->assertGreaterThan(0 , $basicDoc->getId());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $basicDoc->getPersistentState());
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
		$this->assertEquals(array(Event::EVENT_UPDATE, Event::EVENT_UPDATED), $eventCollection->getArrayCopy());
		$eventCollection->exchangeArray(array());

		$this->assertEquals(AbstractDocument::STATE_LOADED, $basicDoc->getPersistentState());
		$this->assertFalse($basicDoc->hasModifiedProperties());
		$this->assertEquals('string 2', $basicDoc->getPStr());
		$this->assertEquals('8.7', $basicDoc->getPDec());
		
		$documentId = $basicDoc->getId();
		$this->getDocumentServices()->getDocumentManager()->reset();


		/* @var $basicDoc2 \Project\Tests\Documents\Basic */
		$basicDoc2 = $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		$basicDoc2->getEventManager()->attach("*", $callBack);

		$this->assertInstanceOf('\Project\Tests\Documents\Basic', $basicDoc2);
		$this->assertEquals(AbstractDocument::STATE_INITIALIZED, $basicDoc2->getPersistentState());
		$this->assertNotSame($basicDoc, $basicDoc2);
		
		$this->assertEquals('string 2', $basicDoc2->getPStr());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $basicDoc2->getPersistentState());
		
		$basicDoc2->delete();
		$this->assertEquals(array(Event::EVENT_LOADED, Event::EVENT_DELETE, Event::EVENT_DELETED), $eventCollection->getArrayCopy());
		$this->assertEquals(AbstractDocument::STATE_DELETED, $basicDoc2->getPersistentState());
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
		$eventCollection = new \ArrayObject();
		$callBack = function (Event $event) use ($eventCollection) {
			$eventCollection[] = $event->getName();
		};
		$localizedDoc->getEventManager()->attach("*", $callBack);

		$dm->pushLCID('fr_FR');

		$this->assertInstanceOf('\Project\Tests\Documents\Localized', $localizedDoc);
		$this->assertEquals('Project_Tests_Localized', $localizedDoc->getDocumentModelName());

		$this->assertEquals(AbstractDocument::STATE_NEW, $localizedDoc->getPersistentState());
		$this->assertLessThan(0 , $localizedDoc->getId());
		$this->assertTrue($localizedDoc->isNew());
		$this->assertFalse($localizedDoc->isDeleted());
		$this->assertEquals(array('LCID', 'creationDate', 'modificationDate'), $localizedDoc->getModifiedPropertyNames());
		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertFalse($localizedDoc->hasModifiedMetas());

		$this->assertEquals('fr_FR', $localizedDoc->getCurrentLCID());
		$cl = $localizedDoc->getCurrentLocalization();
		$this->assertEquals('fr_FR', $cl->getLCID());

		$this->assertNull($localizedDoc->getRefLCID());

		$this->assertNull($localizedDoc->getPStr());
		$this->assertNull($localizedDoc->getPStrOldValue());

		$this->assertNull($cl->getPLStr());
		$this->assertNull($cl->getPLStrOldValue());

		$this->assertInstanceOf('\DateTime', $cl->getCreationDate());
		$this->assertInstanceOf('\DateTime', $cl->getModificationDate());

		$event = new Event(Event::EVENT_CREATE, $localizedDoc);
		$validation = new \Change\Documents\Events\ValidateListener();
		$validation->onValidate($event);
		$errors = $event->getParam('propertiesErrors');

		$this->assertCount(2, $errors);
		$this->assertArrayHasKey('pStr', $errors);
		$this->assertArrayHasKey('pLStr', $errors);

		$localizedDoc->setPStr('string');
		$this->assertEquals('string', $localizedDoc->getPStr());
		$this->assertNull($localizedDoc->getPStrOldValue());

		$cl->setPLStr('string FR');
		$this->assertEquals('string FR', $cl->getPLStr());
		$this->assertNull($cl->getPLStrOldValue());

		$localizedDoc->setRefLCID('fr_FR');
		$localizedDoc->setPInt(50);
		$localizedDoc->setPFloat(0.03);

		$validation->onValidate($event);
		$errors = $event->getParam('propertiesErrors');

		$this->assertNull($errors);
		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertEquals(array('refLCID', 'pStr', 'pInt', 'pFloat', 'LCID', 'creationDate', 'modificationDate', 'pLStr'), $localizedDoc->getModifiedPropertyNames());

		$localizedDoc->save();
		$this->assertEquals(array(Event::EVENT_CREATE, Event::EVENT_CREATED), $eventCollection->getArrayCopy());
		$eventCollection->exchangeArray(array());

		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertEquals(array(), $localizedDoc->getModifiedPropertyNames());

		$this->assertGreaterThan(0 , $localizedDoc->getId());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc->getPersistentState());
		$this->assertFalse($localizedDoc->isNew());
		$this->assertFalse($localizedDoc->isDeleted());
		$this->assertFalse($localizedDoc->hasModifiedProperties());

		$localizedDoc->getCurrentLocalization()->setPLStr('string FR 2');

		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$this->assertEquals('string FR', $localizedDoc->getCurrentLocalization()->getPLStrOldValue());

		$localizedDoc->getCurrentLocalization()->setPLStr('string FR');
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertFalse($localizedDoc->isPropertyModified('pLStr'));
		$this->assertNull($localizedDoc->getCurrentLocalization()->getPLStrOldValue());

		$localizedDoc->getCurrentLocalization()->setPLStr('string FR 2');
		$localizedDoc->getCurrentLocalization()->setPLDec(8.7);
		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertCount(2, $localizedDoc->getModifiedPropertyNames());

		$this->assertNull($localizedDoc->getCurrentLocalization()->getPLDecOldValue());
		$this->assertEquals('string FR', $localizedDoc->getCurrentLocalization()->getPLStrOldValue());

		$localizedDoc->save();
		$this->assertEquals(array(Event::EVENT_UPDATE, Event::EVENT_UPDATED), $eventCollection->getArrayCopy());
		$eventCollection->exchangeArray(array());

		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc->getPersistentState());
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertEquals('string FR 2', $localizedDoc->getCurrentLocalization()->getPLStr());
		$this->assertEquals('8.7', $localizedDoc->getCurrentLocalization()->getPLDec());

		$documentId = $localizedDoc->getId();

		$dm->popLCID();


		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$dm->pushLCID('en_US');

		$this->assertEquals('en_US', $localizedDoc->getCurrentLCID());
		$this->assertEquals('en_US', $localizedDoc->getCurrentLocalization()->getLCID());

		$this->assertEquals(AbstractDocument::STATE_NEW, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$this->assertNull($localizedDoc->getCurrentLocalization()->getPLStr());
		$localizedDoc->getCurrentLocalization()->setPLStr('string EN');
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$localizedDoc->save();
		$this->assertEquals(array(Event::EVENT_UPDATE, Event::EVENT_LOCALIZED_CREATED, Event::EVENT_UPDATED ), $eventCollection->getArrayCopy());
		$eventCollection->exchangeArray(array());

		$this->assertFalse($localizedDoc->hasModifiedProperties());

		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$dm->popLCID();


		$dm->pushLCID('fr_FR');
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$this->assertEquals('fr_FR', $localizedDoc->getCurrentLocalization()->getLCID());
		$this->assertEquals('string FR 2', $localizedDoc->getCurrentLocalization()->getPLStr());
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$this->assertEquals('en_US', $localizedDoc->getCurrentLocalization()->getLCID());
		$this->assertEquals('string EN', $localizedDoc->getCurrentLocalization()->getPLStr());
		$dm->popLCID();

		$dm->reset();

		$dm->pushLCID('en_US');

		/* @var $localizedDoc2 \Project\Tests\Documents\Localized */
		$localizedDoc2 = $this->getDocumentServices()->getDocumentManager()->getDocumentInstance($documentId);
		$localizedDoc2->getEventManager()->attach("*", $callBack);
		$this->assertInstanceOf('\Project\Tests\Documents\Localized', $localizedDoc2);
		$this->assertEquals(AbstractDocument::STATE_INITIALIZED, $localizedDoc2->getPersistentState());
		$this->assertNotSame($localizedDoc, $localizedDoc2);

		$this->assertEquals('string', $localizedDoc2->getPStr());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc2->getPersistentState());

		$localizedDoc2->deleteCurrentLocalization();
		$this->assertEquals(array(Event::EVENT_LOADED, Event::EVENT_LOCALIZED_DELETED), $eventCollection->getArrayCopy());
		$eventCollection->exchangeArray(array());

		$this->assertEquals(AbstractDocument::STATE_DELETED, $localizedDoc2->getCurrentLocalization()->getPersistentState());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $localizedDoc2->getPersistentState());

		$localizedDoc2->delete();
		$this->assertEquals(array(Event::EVENT_DELETE, Event::EVENT_DELETED), $eventCollection->getArrayCopy());
		$this->assertTrue($localizedDoc2->isDeleted());

		$datas = $this->getDocumentServices()->getDocumentManager()->getBackupData($documentId);

		$this->assertArrayHasKey('pStr', $datas);
		$this->assertEquals('string', $datas['pStr']);
		$this->assertArrayHasKey('deletiondate', $datas);
		$this->assertInstanceOf('\DateTime', $datas['deletiondate']);

		$this->assertArrayHasKey('LCID', $datas);
		$this->assertEquals('string FR 2', $datas['LCID']['fr_FR']['pLStr']);
		//$this->assertEquals('string EN', $datas['LCID']['en_US']['pLStr']);
		$dm->popLCID();


		$this->getDocumentServices()->getDocumentManager()->reset();
	}

	public function testCorrection()
	{
		$d1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$d1->setPStr('pStr d1');
		$d1->save();
		$d2 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$d2->setPStr('pStr d2');
		$d2->save();

		/* @var $c1 \Project\Tests\Documents\Correction */
		$c1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Correction');

		$c1->setLabel('c1');
		$c1->getCurrentLocalization()->setPublicationStatus(Publishable::STATUS_DRAFT);
		$c1->setStr1('Str1');
		$c1->setStr2('Str2');
		$c1->getCurrentLocalization()->setStr3('Str3');
		$c1->getCurrentLocalization()->setStr4('Str4');
		$c1->getDocs1()->add($d1);
		$c1->getDocs2()->add($d1);
		$c1->create();
		$this->assertFalse($c1->hasCorrection());
		$this->assertFalse($c1->hasModifiedProperties());

		$c1Id = $c1->getId();
		$this->assertGreaterThan(0, $c1Id);
		$this->assertEquals(AbstractDocument::STATE_LOADED, $c1->getPersistentState());
		$this->assertEquals(AbstractDocument::STATE_LOADED, $c1->getCurrentLocalization()->getPersistentState());

		$c1->getCurrentLocalization()->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$this->assertTrue($c1->isPropertyModified('publicationStatus'));
		$c1->update();
		$this->assertFalse($c1->hasCorrection());
		$this->assertFalse($c1->hasModifiedProperties());

		$c1->setStr1('Str1 v2');
		$c1->setStr2('Str2 v2');
		$c1->getCurrentLocalization()->setStr3('Str3 v2');
		$c1->getCurrentLocalization()->setStr4('Str4 v2');
		$c1->getDocs1()->add($d2);
		$c1->getDocs2()->add($d2);

		$this->assertTrue($c1->hasModifiedProperties());
		$c1->update();
		$this->assertFalse($c1->hasModifiedProperties());

		$this->assertFalse($c1->hasModifiedProperties());
		$this->assertTrue($c1->hasCorrection());

		/* @var $correction \Change\Documents\Correction */
		/* @var $c1 \Project\Tests\Documents\Correction */
		$correction = $c1->getCurrentCorrection();
		$this->assertInstanceOf('\Change\Documents\Correction', $correction);
		$this->assertGreaterThan(0, $correction->getId());
		$this->assertEquals(\Change\Documents\Correction::STATUS_DRAFT, $correction->getStatus());
		$this->assertTrue($correction->isDraft());
		$this->assertEquals(\Change\Documents\Correction::NULL_LCID_KEY, $correction->getLCID());
		$this->assertArrayHasKey('str2', $correction->getDatas());
		$this->assertArrayHasKey('str4', $correction->getDatas());
		$this->assertArrayHasKey('docs2', $correction->getDatas());
		$this->assertEquals(array('str2', 'str4', 'docs2'), $correction->getPropertiesNames());

		$this->assertEquals('Str1 v2', $c1->getStr1());
		$this->assertEquals('Str2 v2', $c1->getStr2());
		$this->assertEquals('Str3 v2', $c1->getCurrentLocalization()->getStr3());
		$this->assertEquals('Str4 v2', $c1->getCurrentLocalization()->getStr4());
		$this->assertCount(2, $c1->getDocs1());
		$this->assertCount(2, $c1->getDocs2());

		$c1->reset();
		$this->assertEquals(AbstractDocument::STATE_INITIALIZED, $c1->getPersistentState());
		$this->assertEquals('Str1 v2', $c1->getStr1());
		$this->assertEquals('Str2', $c1->getStr2());
		$this->assertEquals('Str3 v2', $c1->getCurrentLocalization()->getStr3());
		$this->assertEquals('Str4', $c1->getCurrentLocalization()->getStr4());
		$this->assertCount(2, $c1->getDocs1());
		$this->assertCount(1, $c1->getDocs2());
		$this->assertTrue($c1->hasCorrection());

		$corr = $c1->getCurrentCorrection();
		$this->assertEquals('Str2 v2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4 v2', $corr->getPropertyValue('str4'));
		$this->assertCount(2, $corr->getPropertyValue('docs2'));
		$this->assertEquals(Correction::STATUS_DRAFT, $corr->getStatus());

		$corr->setStatus(Correction::STATUS_PUBLISHABLE);
		$corr->save();

		$c1->mergeCurrentCorrection();

		$this->assertEquals(Correction::STATUS_FILED, $corr->getStatus());
		$this->assertEquals('Str2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4', $corr->getPropertyValue('str4'));
		$this->assertCount(1, $corr->getPropertyValue('docs2'));

		$this->assertEquals('Str2 v2', $c1->getStr2());
		$this->assertEquals('Str4 v2', $c1->getCurrentLocalization()->getStr4());
		$this->assertCount(2, $c1->getDocs2());
		$this->assertFalse($c1->hasCorrection());
		$this->assertFalse($c1->hasModifiedProperties());
	}
}