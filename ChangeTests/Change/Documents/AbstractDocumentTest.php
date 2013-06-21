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
		
		$this->assertEquals(DocumentManager::STATE_NEW, $basicDoc->getPersistentState());
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
		$this->assertTrue($basicDoc->hasModifiedProperties());
		$this->assertEquals(array('creationDate', 'modificationDate', 'pStr', 'pInt', 'pFloat'), $basicDoc->getModifiedPropertyNames());

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

		/* @var $basicDoc2 \Project\Tests\Documents\Basic */
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
		$this->assertTrue($localizedDoc->hasModifiedProperties());
		$this->assertEquals(array('creationDate', 'modificationDate', 'refLCID', 'pStr', 'pLStr', 'pInt', 'pFloat'), $localizedDoc->getModifiedPropertyNames());

		$localizedDoc->save();
		$this->assertFalse($localizedDoc->hasModifiedProperties());
		$this->assertEquals(array(), $localizedDoc->getModifiedPropertyNames());

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


		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$dm->pushLCID('en_US');

		$this->assertEquals('en_US', $localizedDoc->getLCID());

		$this->assertEquals(DocumentManager::STATE_NEW, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$this->assertNull($localizedDoc->getPLStr());
		$localizedDoc->setPLStr('string EN');
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$localizedDoc->save();
		$this->assertFalse($localizedDoc->hasModifiedProperties());

		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$dm->popLCID();


		$dm->pushLCID('fr_FR');
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalization()->getPersistentState());
		$this->assertEquals('fr_FR', $localizedDoc->getLCID());
		$this->assertEquals('string FR 2', $localizedDoc->getPLStr());
		$dm->popLCID();

		$dm->pushLCID('en_US');
		$this->assertEquals(DocumentManager::STATE_LOADED, $localizedDoc->getCurrentLocalization()->getPersistentState());
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
		$this->assertFalse($c1->hasCorrection());
		$this->assertFalse($c1->hasModifiedProperties());

		$c1Id = $c1->getId();
		$this->assertGreaterThan(0, $c1Id);
		$this->assertEquals(DocumentManager::STATE_LOADED, $c1->getPersistentState());
		$this->assertEquals(DocumentManager::STATE_LOADED, $c1->getCurrentLocalization()->getPersistentState());

		$c1->setPublicationStatus(Publishable::STATUS_PUBLISHABLE);
		$this->assertTrue($c1->isPropertyModified('publicationStatus'));
		$c1->update();
		$this->assertFalse($c1->hasCorrection());
		$this->assertFalse($c1->hasModifiedProperties());

		$c1->setStr1('Str1 v2');
		$c1->setStr2('Str2 v2');
		$c1->setStr3('Str3 v2');
		$c1->setStr4('Str4 v2');

		$this->assertTrue($c1->hasModifiedProperties());
		$c1->update();
		$this->assertFalse($c1->hasModifiedProperties());

		$this->assertFalse($c1->hasModifiedProperties());
		$this->assertTrue($c1->hasCorrection());

		/* @var $correction \Change\Documents\Correction */
		$correction = $c1->getCurrentCorrection();
		$this->assertInstanceOf('\Change\Documents\Correction', $correction);
		$this->assertGreaterThan(0, $correction->getId());
		$this->assertEquals(\Change\Documents\Correction::STATUS_DRAFT, $correction->getStatus());
		$this->assertTrue($correction->isDraft());
		$this->assertEquals('fr_FR', $correction->getLCID());
		$this->assertArrayHasKey('str2', $correction->getDatas());
		$this->assertArrayHasKey('str4', $correction->getDatas());
		$this->assertEquals(array('str2', 'str4', 'docs2'), $correction->getPropertiesNames());

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

		$corr = $c1->getCurrentCorrection();
		$this->assertEquals('Str2 v2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4 v2', $corr->getPropertyValue('str4'));
		$this->assertEquals(Correction::STATUS_DRAFT, $corr->getStatus());

		$corr->setStatus(Correction::STATUS_PUBLISHABLE);
		$c1->saveCorrection($corr);

		$c1->publishCorrection();

		$this->assertEquals(Correction::STATUS_FILED, $corr->getStatus());
		$this->assertEquals('Str2', $corr->getPropertyValue('str2'));
		$this->assertEquals('Str4', $corr->getPropertyValue('str4'));

		$this->assertEquals('Str2 v2', $c1->getStr2());
		$this->assertEquals('Str4 v2', $c1->getStr4());
		$this->assertFalse($c1->hasCorrection());
		$this->assertFalse($c1->hasModifiedProperties());
	}

	public function testStringPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPStr());

		$this->assertSame($basicDoc, $basicDoc->setPStr('toto'));
		$this->assertEquals('toto', $basicDoc->getPStr());

		$basicDoc->setPStr(null);
		$this->assertNull($basicDoc->getPStr());
	}

	public function testBooleanPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPBool());

		$this->assertSame($basicDoc, $basicDoc->setPBool(true));
		$this->assertTrue($basicDoc->getPBool());

		$basicDoc->setPBool(false);
		$this->assertFalse($basicDoc->getPBool());

		$basicDoc->setPBool(null);
		$this->assertNull($basicDoc->getPBool());
	}

	public function testIntegerPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPInt());

		$this->assertSame($basicDoc, $basicDoc->setPInt(10));
		$this->assertEquals(10, $basicDoc->getPInt());

		$basicDoc->setPInt(null);
		$this->assertNull($basicDoc->getPInt());
	}

	public function testFloatPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPFloat());

		$this->assertSame($basicDoc, $basicDoc->setPFloat(10.1));
		$this->assertEquals(10.1, $basicDoc->getPFloat());

		$basicDoc->setPFloat(null);
		$this->assertNull($basicDoc->getPFloat());
	}

	public function testDecimalPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPDec());

		$this->assertSame($basicDoc, $basicDoc->setPDec(10.1));
		$this->assertEquals(10.1, $basicDoc->getPDec());

		$basicDoc->setPDec(null);
		$this->assertNull($basicDoc->getPDec());
	}

	public function testDateTimePropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPDaTi());

		$date = new \DateTime();
		$this->assertSame($basicDoc, $basicDoc->setPDaTi($date));
		$this->assertSame($date->format('c'), $basicDoc->getPDaTi()->format('c'));

		$basicDoc->setPDaTi(null);
		$this->assertNull($basicDoc->getPDaTi());

		$this->assertSame($basicDoc, $basicDoc->setPDaTi('2013-06-20T17:45:04+02:00'));
		$this->assertEquals('2013-06-20T17:45:04+02:00', $basicDoc->getPDaTi()->format('c'));
	}

	public function testDatePropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPDa());

		$date = new \DateTime();
		$this->assertSame($basicDoc, $basicDoc->setPDa($date));
		$this->assertSame($date->format('Y-m-d'), $basicDoc->getPDa()->format('Y-m-d'));

		$basicDoc->setPDa(null);
		$this->assertNull($basicDoc->getPDa());

		$this->assertSame($basicDoc, $basicDoc->setPDa('2013-06-20'));
		$this->assertEquals('2013-06-20', $basicDoc->getPDa()->format('Y-m-d'));
	}

	public function testLongStringPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPText());

		$text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor.

		Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor. Lorem ipsum dolor sit amet, consectetur adipiscing elit.

		Aenean ut gravida lorem. Ut turpis felis, pulvinar a semper sed, adipiscing id dolor. Pellentesque auctor nisi id magna consequat sagittis. Curabitur dapibus enim sit amet elit pharetra tincidunt feugiat nisl imperdiet. Ut convallis libero in urna ultrices accumsan. Donec sed odio eros. Donec viverra mi quis quam pulvinar at malesuada arcu rhoncus. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In rutrum accumsan ultricies. Mauris vitae nisi at sem facilisis semper ac in est.';
		$this->assertSame($basicDoc, $basicDoc->setPText($text));
		$this->assertSame($text, $basicDoc->getPText());

		$basicDoc->setPText(null);
		$this->assertNull($basicDoc->getPText());
	}

	public function testJSONPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPJson());

		$data = array('toto' => 'youpi', 'plop' => 12.2, 1 => 'test');
		$json = '{"toto":"youpi","plop":12.2,"1":"test"}';
		$this->assertSame($basicDoc, $basicDoc->setPJson($data));
		$this->assertEquals($data, $basicDoc->getPJson());
		$this->assertEquals($json, $basicDoc->getPJsonString());

		$basicDoc->setPJson(null);
		$this->assertNull($basicDoc->getPJson());
	}

	public function testXMLPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPXml());

		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root><test id=\"tutu\" tata=\"titi\">Toto</test></root>\n";
		$this->assertSame($basicDoc, $basicDoc->setPXml($xml));
		$this->assertEquals($xml, $basicDoc->getPXml());
		$domDoc = $basicDoc->getPXmlDOMDocument();
		$this->assertInstanceOf('DOMDocument', $domDoc);
		$node = $domDoc->getElementsByTagName('test')->item(0);
		$node->removeAttribute('tata');
		$node->removeAttribute('id');
		$node->setAttribute('titi', 'tutu');
		$this->assertSame($basicDoc, $basicDoc->setPXmlDOMDocument($domDoc));
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root><test titi=\"tutu\">Toto</test></root>\n";
		$this->assertEquals($xml, $basicDoc->getPXml());

		$basicDoc->setPXml(null);
		$this->assertNull($basicDoc->getPXml());
	}

	public function testRichtextPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPRt());

		// TODO

		$basicDoc->setPJson(null);
		$this->assertNull($basicDoc->getPJson());
	}

	public function testLobPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPlob());

		$string = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 1000);
		$this->assertSame($basicDoc, $basicDoc->setPlob($string));
		$this->assertSame($string, $basicDoc->getPlob());

		$basicDoc->setPlob(null);
		$this->assertNull($basicDoc->getPlob());
	}

	public function testObjectPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPObj());

		$data = array('toto' => 'youpi', 'plop' => 12.2, 1 => 'test');
		$serialized = serialize($data);
		$this->assertSame($basicDoc, $basicDoc->setPObj($data));
		$this->assertEquals($data, $basicDoc->getPObj());
		$this->assertEquals($serialized, $basicDoc->getPObjString());

		$basicDoc->setPObj(null);
		$this->assertNull($basicDoc->getPObj());
	}

	public function testDocumentIdPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		/* @var $doc1 \Project\Tests\Documents\Localized */
		$doc1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc1->setPStr('Doc1');
		$doc1->setPLStr('Doc1 loc');
		$doc1->save();
		$doc1Id = $doc1->getId();

		$this->assertNull($basicDoc->getPDocId());

		$this->assertSame($basicDoc, $basicDoc->setPDocId($doc1Id));
		$this->assertEquals($doc1Id, $basicDoc->getPDocId());
		$this->assertSame($doc1, $basicDoc->getPDocIdInstance());

		$basicDoc->setPDocId(null);
		$this->assertNull($basicDoc->getPDocId());
	}

	public function testDocumentPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		/* @var $doc1 \Project\Tests\Documents\Localized */
		$doc1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc1->setPStr('Doc1');
		$doc1->setPLStr('Doc1 loc');
		$doc1->save();
		$doc1Id = $doc1->getId();

		$this->assertNull($basicDoc->getPDocInst());

		$this->assertSame($basicDoc, $basicDoc->setPDocInst($doc1));
		$this->assertSame($doc1, $basicDoc->getPDocInst());
		$this->assertEquals($doc1Id, $basicDoc->getPDocInstId());

		$basicDoc->setPDocInst(null);
		$this->assertNull($basicDoc->getPDocInst());
	}

	public function testDocumentArrayPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		/* @var $doc1 \Project\Tests\Documents\Localized */
		$doc1 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc1->setPStr('Doc1');
		$doc1->setPLStr('Doc1 loc');
		$doc1->save();
		$doc1Id = $doc1->getId();

		/* @var $doc2 \Project\Tests\Documents\Localized */
		$doc2 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc2->setPStr('Doc2');
		$doc2->setPLStr('Doc2 loc');
		$doc2->save();
		$doc2Id = $doc2->getId();

		/* @var $doc3 \Project\Tests\Documents\Localized */
		$doc3 = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc3->setPStr('Doc3');
		$doc3->setPLStr('Doc3 loc');
		$doc3->save();
		$doc3Id = $doc3->getId();

		$this->assertEquals(array(), $basicDoc->getPDocArr());

		$this->assertSame($basicDoc, $basicDoc->setPDocArr(array($doc1, $doc2)));
		$this->assertEquals(array($doc1, $doc2), $basicDoc->getPDocArr());

		$this->assertSame($basicDoc, $basicDoc->addPDocArr($doc2));
		$this->assertEquals(array($doc1, $doc2), $basicDoc->getPDocArr());
		$this->assertSame($basicDoc, $basicDoc->addPDocArr($doc3));
		$this->assertEquals(array($doc1, $doc2, $doc3), $basicDoc->getPDocArr());
		$this->assertEquals(array($doc1Id, $doc2Id, $doc3Id), $basicDoc->getPDocArrIds());

		$basicDoc->setPDocArr(array());
		$this->assertEquals(array(), $basicDoc->getPDocArr());

		$this->assertSame($basicDoc, $basicDoc->setPDocArrAtIndex($doc2, 0));
		$this->assertEquals(array($doc2), $basicDoc->getPDocArr());
		$this->assertSame($basicDoc, $basicDoc->setPDocArrAtIndex($doc3, 1));
		$this->assertEquals(array($doc2, $doc3), $basicDoc->getPDocArr());
		$this->assertEquals($doc3, $basicDoc->getPDocArrByIndex(1));
		$this->assertEquals(array($doc2Id, $doc3Id), $basicDoc->getPDocArrIds());

		$basicDoc->setPDocArr(array());
		$this->assertEquals(array(), $basicDoc->getPDocArr());
	}
}