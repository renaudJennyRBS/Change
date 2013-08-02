<?php
namespace ChangeTests\Change\Documents;

class AbstractDocumentPropertiesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
		static::clearDB();
	}

	public function testStringPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPStr());
		$this->assertFalse($basicDoc->isPropertyModified('pStr'));
		$this->assertSame($basicDoc, $basicDoc->setPStr('toto'));
		$this->assertEquals('toto', $basicDoc->getPStr());
		$this->assertTrue($basicDoc->isPropertyModified('pStr'));
		$basicDoc->setPStr(null);
		$this->assertNull($basicDoc->getPStr());

		$this->assertNull($basicDoc->getPStrOldValue());
		$basicDoc->setPStr('default');
		$this->assertTrue($basicDoc->isPropertyModified('pStr'));
		$basicDoc->removeOldPropertyValue('pStr');
		$this->assertFalse($basicDoc->isPropertyModified('pStr'));

		$basicDoc->setPStr(null);
		$this->assertNull($basicDoc->getPStr());
		$this->assertTrue($basicDoc->isPropertyModified('pStr'));
		$this->assertEquals('default', $basicDoc->getPStrOldValue());
	}

	public function testLocalizedStringPropertyAccessors()
	{
		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$localizedDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');

		$this->assertNull($localizedDoc->getPLStr());
		$this->assertFalse($localizedDoc->isPropertyModified('pLStr'));
		$this->assertSame($localizedDoc, $localizedDoc->setPLStr('toto'));
		$this->assertEquals('toto', $localizedDoc->getPLStr());
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$localizedDoc->setPLStr(null);
		$this->assertNull($localizedDoc->getPLStr());

		$this->assertNull($localizedDoc->getPLStrOldValue());
		$localizedDoc->setPLStr('default');
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$localizedDoc->removeOldPropertyValue('pLStr');
		$this->assertFalse($localizedDoc->isPropertyModified('pLStr'));

		$localizedDoc->setPLStr(null);
		$this->assertNull($localizedDoc->getPLStr());
		$this->assertTrue($localizedDoc->isPropertyModified('pLStr'));
		$this->assertEquals('default', $localizedDoc->getPLStrOldValue());
	}

	public function testBooleanPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPBool());
		$this->assertFalse($basicDoc->isPropertyModified('pBool'));
		$this->assertSame($basicDoc, $basicDoc->setPBool(true));
		$this->assertTrue($basicDoc->getPBool());
		$this->assertTrue($basicDoc->isPropertyModified('pBool'));
		$basicDoc->setPBool(false);
		$this->assertFalse($basicDoc->getPBool());
		$this->assertTrue($basicDoc->isPropertyModified('pBool'));

		$basicDoc->setPBool(null);
		$this->assertNull($basicDoc->getPBool());
		$this->assertFalse($basicDoc->isPropertyModified('pBool'));

		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$localizedDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$this->assertNull($localizedDoc->getPLBool());
		$this->assertFalse($localizedDoc->isPropertyModified('pLBool'));
		$this->assertSame($localizedDoc, $localizedDoc->setPLBool(true));
		$this->assertTrue($localizedDoc->getPLBool());
		$this->assertTrue($localizedDoc->isPropertyModified('pLBool'));
		$localizedDoc->setPLBool(false);
		$this->assertFalse($localizedDoc->getPLBool());
		$this->assertTrue($localizedDoc->isPropertyModified('pLBool'));

		$localizedDoc->setPLBool(null);
		$this->assertNull($localizedDoc->getPLBool());
		$this->assertFalse($localizedDoc->isPropertyModified('pLBool'));
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
		$this->assertFalse($basicDoc->isPropertyModified('pFloat'));
		$this->assertSame($basicDoc, $basicDoc->setPFloat(10.1));
		$this->assertEquals(10.1, $basicDoc->getPFloat());
		$this->assertTrue($basicDoc->isPropertyModified('pFloat'));
		$basicDoc->setPFloat(null);
		$this->assertNull($basicDoc->getPFloat());
		$this->assertFalse($basicDoc->isPropertyModified('pFloat'));
		$basicDoc->setPFloat(0);
		$this->assertSame(0.0, $basicDoc->getPFloat());
		$this->assertTrue($basicDoc->isPropertyModified('pFloat'));
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

		$basicDoc->setPDec(0);
		$this->assertSame(0.0, $basicDoc->getPDec());
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

	public function testStorageUriPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPStorUri());

		$text = 'change://tmp/test.txt';
		$this->assertSame($basicDoc, $basicDoc->setPStorUri($text));
		$this->assertSame($text, $basicDoc->getPStorUri());
		$obj = $basicDoc->getPStorUriItemInfo();
		$this->assertInstanceOf('\Change\Storage\ItemInfo', $obj);
		$this->assertEquals($text, $obj->getPathname());
		$basicDoc->setPStorUri(null);
		$this->assertNull($basicDoc->getPText());

		$basicDoc->setPStorUri('http://tmp/test.txt');
		$l = new \Change\Documents\Events\ValidateListener();
		$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_CREATE, $basicDoc);
		$l->onValidate($event);
		$pe = $event->getParam('propertiesErrors');
		$this->assertArrayHasKey('pStorUri', $pe);
		$this->assertEquals('\'http://tmp/test.txt\' doit être une URI de stockage valide.', $pe['pStorUri'][0]);

		$basicDoc->setPStorUri($text);
		$event = new \Change\Documents\Events\Event(\Change\Documents\Events\Event::EVENT_CREATE, $basicDoc);
		$l->onValidate($event);
		$pe = $event->getParam('propertiesErrors');
		$this->assertArrayNotHasKey('pStorUri', $pe);
	}

	public function testJSONPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		$this->assertNull($basicDoc->getPJson());
		$this->assertFalse($basicDoc->isPropertyModified('pJson'));

		$data = array('toto' => 'youpi', 'plop' => 12.2, 1 => 'test');
		$json = '{"toto":"youpi","plop":12.2,"1":"test"}';
		$this->assertSame($basicDoc, $basicDoc->setPJson($data));
		$this->assertEquals($data, $basicDoc->getPJson());
		$this->assertEquals($json, $basicDoc->getPJsonString());
		$this->assertTrue($basicDoc->isPropertyModified('pJson'));
		$basicDoc->setPJson(null);
		$this->assertFalse($basicDoc->isPropertyModified('pJson'));
		$this->assertNull($basicDoc->getPJson());

		/* @var $localizedDoc \Project\Tests\Documents\Localized */
		$localizedDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$this->assertNull($localizedDoc->getPLJson());
		$this->assertFalse($localizedDoc->isPropertyModified('pLJson'));

		$data = array('toto' => 'youpi', 'plop' => 12.2, 1 => 'test');
		$json = '{"toto":"youpi","plop":12.2,"1":"test"}';
		$this->assertSame($localizedDoc, $localizedDoc->setPLJson($data));
		$this->assertEquals($data, $localizedDoc->getPLJson());
		$this->assertEquals($json, $localizedDoc->getPLJsonString());
		$this->assertTrue($localizedDoc->isPropertyModified('pLJson'));
		$localizedDoc->setPLJson(null);
		$this->assertFalse($localizedDoc->isPropertyModified('pLJson'));
		$this->assertNull($localizedDoc->getPLJson());
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

		$this->assertInstanceOf('Change\Documents\RichtextProperty', $basicDoc->getPRt());
		$this->assertTrue($basicDoc->getPRt()->isEmpty());
		$this->assertFalse($basicDoc->getPRt()->isModified());
		$this->assertFalse($basicDoc->isPropertyModified('pRt'));

		$string = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 1000);
		$basicDoc->getPRt()->setRawText($string);
		$this->assertSame($string, $basicDoc->getPRt()->getRawText());
		$this->assertTrue($basicDoc->isPropertyModified('pRt'));
		$this->assertTrue($basicDoc->getPRt()->isModified());
		$this->assertSame($basicDoc, $basicDoc->setPRt(null));
		$this->assertInstanceOf('Change\Documents\RichtextProperty', $basicDoc->getPRt());
		$this->assertTrue($basicDoc->getPRt()->isEmpty());
		$this->assertFalse($basicDoc->getPRt()->isModified());
		$this->assertFalse($basicDoc->isPropertyModified('pRt'));
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
		$doc1 = $this->getNewReadonlyDocument('Project_Tests_Localized', 200);
		$doc1->setPStr('Doc1');
		$doc1->setPLStr('Doc1 loc');
		$doc1Id = $doc1->getId();

		$this->assertSame(0, $basicDoc->getPDocId());
		$this->assertFalse($basicDoc->isPropertyModified('pDocId'));
		$this->assertSame($basicDoc, $basicDoc->setPDocId($doc1Id));
		$this->assertEquals($doc1Id, $basicDoc->getPDocId());
		$this->assertSame($doc1, $basicDoc->getPDocIdInstance());
		$this->assertTrue($basicDoc->isPropertyModified('pDocId'));

		$basicDoc->setPDocId(null);
		$this->assertSame(0, $basicDoc->getPDocId());
		$this->assertFalse($basicDoc->isPropertyModified('pDocId'));
		$basicDoc->setPDocId(0);
		$this->assertSame(0, $basicDoc->getPDocId());
		$this->assertFalse($basicDoc->isPropertyModified('pDocId'));
	}

	public function testDocumentPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		/* @var $doc1 \Project\Tests\Documents\Localized */
		$doc1 = $this->getNewReadonlyDocument('Project_Tests_Localized', 201);
		$doc1->setPStr('Doc1');
		$doc1->setPLStr('Doc1 loc');
		$doc1Id = $doc1->getId();

		$this->assertNull($basicDoc->getPDocInst());
		$this->assertSame(0, $basicDoc->getPDocInstId());
		$this->assertFalse($basicDoc->isPropertyModified('pDocInst'));

		$this->assertSame($basicDoc, $basicDoc->setPDocInst($doc1));
		$this->assertSame($doc1, $basicDoc->getPDocInst());
		$this->assertEquals($doc1Id, $basicDoc->getPDocInstId());
		$this->assertTrue($basicDoc->isPropertyModified('pDocInst'));

		$basicDoc->setPDocInst(null);
		$this->assertNull($basicDoc->getPDocInst());
		$this->assertSame(0, $basicDoc->getPDocInstId());
		$this->assertFalse($basicDoc->isPropertyModified('pDocInst'));
	}

	public function testDocumentArrayPropertyAccessors()
	{
		/* @var $basicDoc \Project\Tests\Documents\Basic */
		$basicDoc = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');

		/* @var $doc1 \Project\Tests\Documents\Localized */
		$doc1 = $this->getNewReadonlyDocument('Project_Tests_Localized', 202);
		$doc1->setPStr('Doc1');
		$doc1->setPLStr('Doc1 loc');
		$doc1Id = $doc1->getId();

		/* @var $doc2 \Project\Tests\Documents\Localized */
		$doc2 = $this->getNewReadonlyDocument('Project_Tests_Localized', 203);
		$doc2->setPStr('Doc2');
		$doc2->setPLStr('Doc2 loc');
		$doc2Id = $doc2->getId();

		/* @var $doc3 \Project\Tests\Documents\Localized */
		$doc3 = $this->getNewReadonlyDocument('Project_Tests_Localized', 204);
		$doc3->setPStr('Doc3');
		$doc3->setPLStr('Doc3 loc');
		$doc3Id = $doc3->getId();

		$this->assertInstanceOf('\Change\Documents\DocumentArrayProperty', $basicDoc->getPDocArr());
		$this->assertEquals('Project_Tests_Localized', $basicDoc->getPDocArr()->getModelName());

		$this->assertSame($basicDoc, $basicDoc->setPDocArr(array($doc1, $doc2)));
		$this->assertEquals(2, $basicDoc->getPDocArr()->count());
		$this->assertEquals(array($doc1, $doc2), $basicDoc->getPDocArr()->toArray());

		$this->assertSame($basicDoc->getPDocArr(),  $basicDoc->getPDocArr()->add($doc2));
		$this->assertEquals(array($doc1, $doc2), $basicDoc->getPDocArr()->toArray());
		$this->assertEquals(2, $basicDoc->getPDocArr()->count());
		$basicDoc->getPDocArr()->add($doc3);
		$this->assertEquals(array($doc1, $doc2, $doc3), $basicDoc->getPDocArr()->toArray());
		$this->assertEquals(3, $basicDoc->getPDocArr()->count());
		$this->assertEquals(array($doc1Id, $doc2Id, $doc3Id), $basicDoc->getPDocArrIds());

		$this->assertSame($basicDoc->getPDocArr(),  $basicDoc->getPDocArr()->remove($doc1));
		$this->assertEquals(2, $basicDoc->getPDocArr()->count());
		$this->assertEquals(array($doc2, $doc3), $basicDoc->getPDocArr()->toArray());
		$basicDoc->getPDocArr()[1] = $doc1;
		$this->assertEquals(2, $basicDoc->getPDocArr()->count());
		$this->assertEquals(array($doc2, $doc1), $basicDoc->getPDocArr()->toArray());
		$this->assertEquals(1, $basicDoc->getPDocArr()->indexOf($doc1));
		$this->assertEquals(0, $basicDoc->getPDocArr()->indexOf($doc2));

		$this->assertFalse($basicDoc->getPDocArr()->indexOf($doc3));

		$basicDoc->setPDocArr(array());
		$this->assertEquals(array(), $basicDoc->getPDocArr()->toArray());
		$this->assertEquals(array(), $basicDoc->getPDocArr()->getIds());
		$this->assertEquals(0, $basicDoc->getPDocArr()->count());
	}
}