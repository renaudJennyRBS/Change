<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace ChangeTests\Documents\Generators;

/**
* @name \ChangeTests\Documents\Generators\InlineClassGeneratorTest
*/
class InlineClassGeneratorTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	public function testCompile()
	{
		$definitionPath = __DIR__ . '/TestAssets/InlineCompile.xml';
		$doc = new \DOMDocument('1.0', 'utf-8');
		$doc->load($definitionPath);

		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$model = new \Change\Documents\Generators\Model('ChangeTests', 'Generators', 'inline');
		$model->setXmlInlineElement($doc->documentElement, $compiler);
		$model->validateInheritance();
		$codeDir = $this->getApplication()->getWorkspace()->tmpPath('InlineCompile');

		$generator = new \Change\Documents\Generators\ModelClass();
		$generator->savePHPCode($compiler, $model, $codeDir);

		$phpClassPath = $codeDir . '/ChangeTests/Generators/Documents/InlineCompileModel.php';
		$this->assertFileExists($phpClassPath);
		include_once $phpClassPath;
		$this->assertTrue(class_exists('\Compilation\ChangeTests\Generators\Documents\InlineCompileModel'));

		$generator = new \Change\Documents\Generators\BaseInlineClass();
		$generator->savePHPCode($compiler, $model, $codeDir);

		$phpClassPath = $codeDir . '/ChangeTests/Generators/Documents/InlineCompile.php';
		$this->assertFileExists($phpClassPath);
		include_once $phpClassPath;
		$this->assertTrue(class_exists('\Compilation\ChangeTests\Generators\Documents\InlineCompile'));

		$generator = new \Change\Documents\Generators\InlineLocalizedClass();
		$generator->savePHPCode($compiler, $model, $codeDir);

		$phpClassPath = $codeDir . '/ChangeTests/Generators/Documents/LocalizedInlineCompile.php';
		$this->assertFileExists($phpClassPath);
		include_once $phpClassPath;
		$this->assertTrue(class_exists('\Compilation\ChangeTests\Generators\Documents\LocalizedInlineCompile'));


		$model = $this->getApplicationServices()->getModelManager()->getModelByName('ChangeTests_Generators_InlineCompile');
		$this->assertInstanceOf('\Compilation\ChangeTests\Generators\Documents\InlineCompileModel', $model);
		$this->assertTrue($model->isInline());

		include_once __DIR__ . '/TestAssets/InlineCompile.inc';

		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModel($model, false);
		$this->assertInstanceOf('\ChangeTests\Generators\Documents\InlineCompile', $instance);
		$instance->cleanUp();
	}

	/**
	 * @depends testCompile
	 */
	public function testStateMethods()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile', false);
		$this->assertTrue($instance->isNew());
		$this->assertFalse($instance->isModified());

		$this->assertFalse($instance->isNew(false));
		$this->assertFalse($instance->isNew());

		$this->assertTrue($instance->isModified(true));
		$this->assertTrue($instance->isModified());
		$instance->cleanUp();

		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');
		$this->assertTrue($instance->isNew());
		$this->assertFalse($instance->isModified());
		$instance->cleanUp();
	}

	/**
	 * @depends testStateMethods
	 */
	public function testDbData()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile', false);
		$instance->isModified(true);
		$instance->isNew(true);

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('model', $dbData);
		$this->assertEquals('ChangeTests_Generators_InlineCompile', $dbData['model']);
		$this->assertFalse($instance->isNew());
		$this->assertFalse($instance->isModified());

		$instance->isModified(true);
		$instance->isNew(true);
		$instance->dbData($dbData);
		$this->assertFalse($instance->isNew());
		$this->assertFalse($instance->isModified());

		$instance->cleanUp();
	}

	/**
	 * @depends testDbData
	 */
	public function testStringProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPString());

		$this->assertSame($instance, $instance->setPString('test'));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals('test', $instance->getPString());
		$instance->setPString('test');
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPString());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pString')->setDefaultValue('default');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('default', $instance->getPString());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pString', $dbData);
		$this->assertEquals('default', $dbData['pString']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pString'] = 'dbData';
		$instance->dbData($dbData);
		$this->assertEquals('dbData', $instance->getPString());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}


	/**
	 * @depends testStringProperty
	 */
	public function testDecimalProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPDecimal());

		$this->assertSame($instance, $instance->setPDecimal(0.333333));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals(0.333333, $instance->getPDecimal());
		$instance->setPDecimal(0.333333);
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPDecimal());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pDecimal')->setDefaultValue('0.343434');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(0.343434, $instance->getPDecimal());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pDecimal', $dbData);
		$this->assertEquals(0.343434, $dbData['pDecimal']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pDecimal'] = 0.333333;
		$instance->dbData($dbData);
		$this->assertEquals(0.333333, $instance->getPDecimal());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testDecimalProperty
	 */
	public function testFloatProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPFloat());

		$this->assertSame($instance, $instance->setPFloat(0.333333));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals(0.333333, $instance->getPFloat());
		$instance->setPFloat(0.333333);
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPFloat());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pFloat')->setDefaultValue('0.343434');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(0.343434, $instance->getPFloat());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pFloat', $dbData);
		$this->assertEquals(0.343434, $dbData['pFloat']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pFloat'] = 0.333333;
		$instance->dbData($dbData);
		$this->assertEquals(0.333333, $instance->getPFloat());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}


	/**
	 * @depends testFloatProperty
	 */
	public function testIntegerProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPInteger());

		$this->assertSame($instance, $instance->setPInteger(10));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals(10, $instance->getPInteger());
		$instance->setPInteger(10);
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPInteger());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pInteger')->setDefaultValue('20');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(20, $instance->getPInteger());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pInteger', $dbData);
		$this->assertEquals(20, $dbData['pInteger']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pInteger'] = 10;
		$instance->dbData($dbData);
		$this->assertEquals(10, $instance->getPInteger());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testIntegerProperty
	 */
	public function testLongStringProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPLongString());

		$this->assertSame($instance, $instance->setPLongString('test'));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals('test', $instance->getPLongString());
		$instance->setPLongString('test');
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPLongString());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pLongString')->setDefaultValue('default');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('default', $instance->getPLongString());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pLongString', $dbData);
		$this->assertEquals('default', $dbData['pLongString']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pLongString'] = 'dbData';
		$instance->dbData($dbData);
		$this->assertEquals('dbData', $instance->getPLongString());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}


	/**
	 * @depends testLongStringProperty
	 */
	public function testBooleanProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPBoolean());

		$this->assertSame($instance, $instance->setPBoolean(true));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals(true, $instance->getPBoolean());
		$instance->setPBoolean(true);
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPBoolean());
		$this->assertEquals(1, $callBackCount);

		$instance->getDocumentModel()->getProperty('pBoolean')->setDefaultValue('true');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(true, $instance->getPBoolean());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pBoolean', $dbData);
		$this->assertEquals(true, $dbData['pBoolean']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pBoolean'] = false;
		$instance->dbData($dbData);
		$this->assertEquals(false, $instance->getPBoolean());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}


	/**
	 * @depends testBooleanProperty
	 */
	public function testDateProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPDate());

		$date = '2014-07-17T16:31:33+0200';
		$date2 = '2014-07-18';

		$this->assertSame($instance, $instance->setPDate($date));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals(new \DateTime('2014-07-17T00:00:00+0000'), $instance->getPDate());
		$instance->setPDate(new \DateTime('2014-07-17T00:00:00+0000'));
		$this->assertEquals(1, $callBackCount);
		$instance->setPDate('2014-07-17');
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPDate());
		$this->assertEquals(1, $callBackCount);

		$instance->getDocumentModel()->getProperty('pDate')->setDefaultValue($date2);
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(new \DateTime('2014-07-18T00:00:00+0000'), $instance->getPDate());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pDate', $dbData);
		$this->assertEquals($date2, $dbData['pDate']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pDate'] = '2014-07-17';
		$instance->dbData($dbData);
		$this->assertEquals(new \DateTime('2014-07-17T00:00:00+0000'), $instance->getPDate());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testDateProperty
	 */
	public function testDateTimeProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPDateTime());

		$date = '2014-07-17T16:31:33+0200';
		$date2 = '2014-07-18T14:55:00+0000';

		$this->assertSame($instance, $instance->setPDateTime($date));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals('2014-07-17T16:31:33+0200', $instance->getPDateTime()->format(\DateTime::ISO8601));
		$instance->setPDateTime(new \DateTime('2014-07-17T16:31:33+0200'));
		$this->assertEquals(1, $callBackCount);
		$instance->setPDateTime('2014-07-17T14:31:33+0000');
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPDateTime());
		$this->assertEquals(1, $callBackCount);

		$instance->getDocumentModel()->getProperty('pDateTime')->setDefaultValue($date2);
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals($date2, $instance->getPDateTime()->format(\DateTime::ISO8601));
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pDateTime', $dbData);
		$this->assertEquals($date2, $dbData['pDateTime']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pDateTime'] = $date;
		$instance->dbData($dbData);
		$this->assertEquals($date, $instance->getPDateTime()->format(\DateTime::ISO8601));
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testDateTimeProperty
	 */
	public function testDocumentProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPDocument());
		$this->assertSame(0, $instance->getPDocumentId());

		$doc101 = $this->getNewReadonlyDocument('Project_Tests_Basic', 101);

		$doc102 = $this->getNewReadonlyDocument('Project_Tests_Basic', 102);

		$this->assertSame($instance, $instance->setPDocument($doc101));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());
		$this->assertSame($doc101, $instance->getPDocument());
		$this->assertSame(101, $instance->getPDocumentId());

		$instance->setPDocument($doc101);
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPDocument());
		$this->assertSame(0, $instance->getPDocumentId());
		$this->assertEquals(1, $callBackCount);

		$this->assertSame($instance, $instance->setPDocument($doc102));
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pDocument', $dbData);
		$this->assertEquals(102, $dbData['pDocument']);

		$dbData['pDocument'] = 101;
		$instance->dbData($dbData);
		$this->assertEquals($doc101, $instance->getPDocument());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testDocumentProperty
	 */
	public function testDocumentArrayProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$array = $instance->getPDocumentArray();
		$this->assertInstanceOf('\Change\Documents\DocumentArrayProperty', $array);
		$this->assertEquals(0, $array->count());
		$this->assertSame(0, $instance->getPDocumentArrayCount());
		$this->assertSame([], $instance->getPDocumentArrayIds());

		$doc101 = $this->getNewReadonlyDocument('Project_Tests_Basic', 101);

		$doc102 = $this->getNewReadonlyDocument('Project_Tests_Basic', 102);

		$doc103 = $this->getNewReadonlyDocument('Project_Tests_Basic', 103);

		$this->assertSame($instance, $instance->setPDocumentArray([$doc101, $doc103]));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());
		$this->assertSame($array, $instance->getPDocumentArray());
		$this->assertEquals([101, 103], $array->getIds());
		$this->assertSame(2, $instance->getPDocumentArrayCount());
		$this->assertSame([101, 103], $instance->getPDocumentArrayIds());


		$instance->getPDocumentArray()->add($doc102);
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals(3, $array->count());

		$instance->unsetProperties();
		$this->assertNotSame($array, $instance->getPDocumentArray());
		$this->assertEquals(1, $callBackCount);
		$array = $instance->getPDocumentArray();
		$this->assertInstanceOf('\Change\Documents\DocumentArrayProperty', $array);

		$this->assertSame($instance, $instance->setPDocumentArray([$doc102, $doc103]));
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pDocumentArray', $dbData);
		$this->assertEquals([102, 103], $dbData['pDocumentArray']);

		$dbData['pDocumentArray'] = [101, 102];
		$instance->dbData($dbData);
		$this->assertNotSame($array, $instance->getPDocumentArray());

		$this->assertEquals([101, 102], $instance->getPDocumentArray()->getIds());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}


	/**
	 * @depends testDocumentArrayProperty
	 */
	public function testDocIdProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertSame(0, $instance->getPDocId());
		$this->assertNull($instance->getPDocIdInstance());

		$doc101 = $this->getNewReadonlyDocument('Project_Tests_Basic', 101);

		$this->assertSame($instance, $instance->setPDocId(101));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());
		$this->assertSame(101, $instance->getPDocId());
		$this->assertSame($doc101, $instance->getPDocIdInstance());

		$instance->setPDocId($doc101);
		$this->assertEquals(1, $callBackCount);
		$this->assertSame(101, $instance->getPDocId());

		$instance->unsetProperties();
		$this->assertSame(0, $instance->getPDocId());
		$this->assertEquals(1, $callBackCount);

		$this->assertSame($instance, $instance->setPDocId(102));
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pDocId', $dbData);
		$this->assertEquals(102, $dbData['pDocId']);

		$dbData['pDocId'] = 101;
		$instance->dbData($dbData);
		$this->assertSame(101, $instance->getPDocId());
		$this->assertEquals(2, $callBackCount);
		$instance->cleanUp();
	}

	/**
	 * @depends testDocIdProperty
	 */
	public function testJSONProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPJSON());

		$json1 = ['test' => 0, 'test2' => true, 'Test3' => ['v1', 'v2']];
		$this->assertSame($instance, $instance->setPJSON($json1));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());
		$this->assertSame($json1, $instance->getPJSON());

		$instance->setPJSON($json1);
		$this->assertEquals(1, $callBackCount);
		$this->assertSame($json1, $instance->getPJSON());

		$instance->unsetProperties();
		$this->assertNull($instance->getPJSON());
		$this->assertEquals(1, $callBackCount);
		$instance->setPJSON(null);
		$this->assertNull($instance->getPJSON());
		$this->assertEquals(1, $callBackCount);

		$json2 = ['test' => 4, 'test2' => false, 'Test3' => ['v1', 'v2']];
		$this->assertSame($instance, $instance->setPJSON($json2));
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pJSON', $dbData);
		$this->assertEquals($json2, $dbData['pJSON']);

		$dbData['pJSON'] = $json1;
		$instance->dbData($dbData);
		$this->assertSame($json1, $instance->getPJSON());
		$this->assertEquals(2, $callBackCount);
		$instance->cleanUp();
	}


	/**
	 * @depends testJSONProperty
	 */
	public function testRichTextProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$richtextProperty = $instance->getPRichText();
		$this->assertInstanceOf('\Change\Documents\RichtextProperty', $richtextProperty);
		$this->assertTrue($richtextProperty->isEmpty());


		$richtext1 = new \Change\Documents\RichtextProperty();
		$richtext1->setEditor('Html')->setRawText('Dummy text');

		$richtext2 = new \Change\Documents\RichtextProperty();
		$richtext2->setEditor('Html')->setRawText('Dummy text 2');

		$this->assertSame($instance, $instance->setPRichText($richtext1));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());
		$this->assertSame($richtext1, $instance->getPRichText());

		$instance->setPRichText($richtext1->toArray());
		$this->assertSame($richtext1, $instance->getPRichText());
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNotSame($richtext1, $instance->getPRichText());
		$this->assertEquals(1, $callBackCount);
		$richtextProperty = $instance->getPRichText();
		$this->assertInstanceOf('\Change\Documents\RichtextProperty', $richtextProperty);

		$instance->setPRichText($richtext2->toArray());
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pRichText', $dbData);
		$this->assertEquals($richtext2->toArray(), $dbData['pRichText']);

		$dbData['pRichText'] = $richtext1->toArray();
		$instance->dbData($dbData);

		$this->assertEquals($richtext1->toArray(), $instance->getPRichText()->toArray());
		$this->assertEquals(2, $callBackCount);

		$instance->setPRichText(null);
		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pRichText', $dbData);
		$this->assertNull($dbData['pRichText']);

		$instance->cleanUp();
	}


	/**
	 * @depends testRichTextProperty
	 */
	public function testLobProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPLob());

		$this->assertSame($instance, $instance->setPLob('test'));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals('test', $instance->getPLob());
		$instance->setPLob('test');
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPLob());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pLob')->setDefaultValue('default');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('default', $instance->getPLob());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pLob', $dbData);
		$this->assertEquals('default', $dbData['pLob']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pLob'] = 'dbData';
		$instance->dbData($dbData);
		$this->assertEquals('dbData', $instance->getPLob());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testLobProperty
	 */
	public function testStorageUriProperty()
	{
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $this->getApplicationServices()->getDocumentManager()->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});

		$this->assertNull($instance->getPStorageUri());

		$this->assertSame($instance, $instance->setPStorageUri('test'));
		$this->assertEquals(1, $callBackCount);
		$this->assertTrue($instance->isModified());

		$this->assertEquals('test', $instance->getPStorageUri());
		$instance->setPStorageUri('test');
		$this->assertEquals(1, $callBackCount);

		$instance->unsetProperties();
		$this->assertNull($instance->getPStorageUri());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('pStorageUri')->setDefaultValue('default');
		$instance->setDefaultValues();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('default', $instance->getPStorageUri());
		$this->assertFalse($instance->isModified());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('pStorageUri', $dbData);
		$this->assertEquals('default', $dbData['pStorageUri']);
		$this->assertEquals(2, $callBackCount);

		$dbData['pStorageUri'] = 'dbData';
		$instance->dbData($dbData);
		$this->assertEquals('dbData', $instance->getPStorageUri());
		$this->assertEquals(2, $callBackCount);

		$instance->cleanUp();
	}

	/**
	 * @depends testStorageUriProperty
	 */
	public function testLocalizedProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();

		$this->assertCount(0, $instance->getLCIDArray());
		$this->assertNull($instance->getRefLCID());
		$localizedPart = $instance->getRefLocalization();
		$this->assertInstanceOf('\Compilation\ChangeTests\Generators\Documents\LocalizedInlineCompile', $localizedPart);
		$this->assertEquals($refLCID, $instance->getRefLCID());
		$this->assertEquals($refLCID, $localizedPart->getLCID());
		$this->assertTrue($localizedPart->isNew());
		$this->assertFalse($localizedPart->isModified());
		$this->assertTrue($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertSame($localizedPart, $instance->getCurrentLocalization());
		$this->assertCount(1, $instance->getLCIDArray());

		$localizedPart->setLString('test');
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(2, $callBackCount);

		$documentManager->pushLCID('en_US');
		$enLocalizedPart = $instance->getCurrentLocalization();

		$this->assertEquals('en_US', $enLocalizedPart->getLCID());
		$this->assertTrue($enLocalizedPart->isNew());
		$this->assertFalse($enLocalizedPart->isModified());
		$this->assertTrue($enLocalizedPart->isEmpty());
		$this->assertEquals(2, $callBackCount);
		$this->assertCount(2, $instance->getLCIDArray());

		$dbData = $instance->dbData();
		$this->assertArrayHasKey('_LCID', $dbData);
		$this->assertCount(1, $dbData['_LCID']);
		$this->assertArrayHasKey(0, $dbData['_LCID']);
		$this->assertEquals('fr_FR', $dbData['_LCID'][0]['LCID']);

		$this->assertEquals(2, $callBackCount);
		$this->assertFalse($localizedPart->isNew());
		$this->assertFalse($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());

		$this->assertTrue($enLocalizedPart->isNew());
		$this->assertFalse($enLocalizedPart->isModified());
		$this->assertTrue($enLocalizedPart->isEmpty());

		$instance->deleteCurrentLocalization();

		$this->assertNotSame($enLocalizedPart, $instance->getCurrentLocalization());
		$enLocalizedPart->setLString('test');
		$this->assertEquals(2, $callBackCount);

		$instance->getCurrentLocalization()->setLString('test2');
		$this->assertEquals(3, $callBackCount);

		$documentManager->popLCID();
		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedProperty
	 */
	public function testLocalizedStringProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLString());
		$this->assertSame($localizedPart, $localizedPart->setLString('test')) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals('test', $localizedPart->getLString());
		$localizedPart->setLString('test');
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLString('test 2');
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('test 2', $dbData['_LCID'][0]['lString']);

		$localizedPart->setLString('test');
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals('test 2', $localizedPart->getLString());
		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedStringProperty
	 */
	public function testLocalizedLongStringProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLLongString());

		$this->assertSame($localizedPart, $localizedPart->setLLongString('test')) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals('test', $localizedPart->getLLongString());
		$localizedPart->setLLongString('test');
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLLongString('test 2');
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('test 2', $dbData['_LCID'][0]['lLongString']);

		$localizedPart->setLLongString('test');
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals('test 2', $localizedPart->getLLongString());
		$instance->cleanUp();
	}


	/**
	 * @depends testLocalizedLongStringProperty
	 */
	public function testLocalizedLobProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLLob());

		$this->assertSame($localizedPart, $localizedPart->setLLob('test')) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals('test', $localizedPart->getLLob());
		$localizedPart->setLLob('test');
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLLob('test 2');
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('test 2', $dbData['_LCID'][0]['lLob']);

		$localizedPart->setLLob('test');
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals('test 2', $localizedPart->getLLob());
		$instance->cleanUp();
	}


	/**
	 * @depends testLocalizedLobProperty
	 */
	public function testLocalizedStorageUriProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLStorageUri());

		$this->assertSame($localizedPart, $localizedPart->setLStorageUri('test')) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals('test', $localizedPart->getLStorageUri());
		$localizedPart->setLStorageUri('test');
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLStorageUri('test 2');
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals('test 2', $dbData['_LCID'][0]['lStorageUri']);

		$localizedPart->setLStorageUri('test');
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals('test 2', $localizedPart->getLStorageUri());
		$instance->cleanUp();
	}


	/**
	 * @depends testLocalizedStorageUriProperty
	 */
	public function testLocalizedIntegerProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLInteger());

		$this->assertSame($localizedPart, $localizedPart->setLInteger(10)) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals(10, $localizedPart->getLInteger());
		$localizedPart->setLInteger(10);
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLInteger(20);
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(20, $dbData['_LCID'][0]['lInteger']);

		$localizedPart->setLInteger(10);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals(20, $localizedPart->getLInteger());
		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedIntegerProperty
	 */
	public function testLocalizedDecimalProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLDecimal());

		$this->assertSame($localizedPart, $localizedPart->setLDecimal(10.3356)) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals(10.3356, $localizedPart->getLDecimal());
		$localizedPart->setLDecimal(10.3356);
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLDecimal(20.37854);
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(20.37854, $dbData['_LCID'][0]['lDecimal']);

		$localizedPart->setLDecimal(10.3356);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals(20.37854, $localizedPart->getLDecimal());
		$instance->cleanUp();
	}


	/**
	 * @depends testLocalizedDecimalProperty
	 */
	public function testLocalizedFloatProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLFloat());

		$this->assertSame($localizedPart, $localizedPart->setLFloat(10.3356)) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals(10.3356, $localizedPart->getLFloat());
		$localizedPart->setLFloat(10.3356);
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLFloat(20.37854);
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(20.37854, $dbData['_LCID'][0]['lFloat']);

		$localizedPart->setLFloat(10.3356);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals(20.37854, $localizedPart->getLFloat());
		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedFloatProperty
	 */
	public function testLocalizedBooleanProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLBoolean());

		$this->assertSame($localizedPart, $localizedPart->setLBoolean(false)) ;
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);
		$this->assertFalse($localizedPart->getLBoolean());
		$localizedPart->setLBoolean(false);
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLBoolean(true);
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertTrue($dbData['_LCID'][0]['lBoolean']);

		$localizedPart->setLBoolean(false);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertTrue($localizedPart->getLBoolean());
		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedBooleanProperty
	 */
	public function testLocalizedDateProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLDate());

		$date = '2014-07-17T16:31:33+0200';
		$date2 = '2014-07-18';

		$this->assertSame($localizedPart, $localizedPart->setLDate($date));
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);

		$this->assertEquals(new \DateTime('2014-07-17T00:00:00+0000'), $localizedPart->getLDate());

		$localizedPart->setLDate(new \DateTime('2014-07-17T16:31:33+0000'));
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLDate('2014-07-17');
		$this->assertEquals(1, $callBackCount);

		$localizedPart->unsetProperties();
		$this->assertNull($localizedPart->getLDate());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('lDate')->setDefaultValue($date2);
		$instance->resetCurrentLocalized();
		$localizedPart = $instance->getRefLocalization();

		$this->assertEquals(new \DateTime('2014-07-18T00:00:00+0000'), $localizedPart->getLDate());
		$this->assertEquals(1, $callBackCount);
		$this->assertFalse($localizedPart->isModified());

		$localizedPart->isModified(true);
		$dbData = $instance->dbData();
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals($date2, $dbData['_LCID'][0]['lDate']);

		$localizedPart->setLDate($date);
		$this->assertEquals(2, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->getDocumentModel()->getProperty('lDate')->setDefaultValue(null);
		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(2, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals(new \DateTime('2014-07-18T00:00:00+0000'), $localizedPart->getLDate());

		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedDateProperty
	 */
	public function testLocalizedDateTimeProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLDateTime());

		$date = '2014-07-17T16:31:33+0200';
		$date2 = '2014-07-18T04:04:33+0200';

		$this->assertSame($localizedPart, $localizedPart->setLDateTime($date));
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);

		$this->assertEquals(new \DateTime($date), $localizedPart->getLDateTime());

		$localizedPart->setLDateTime(new \DateTime($date));
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLDateTime($date);
		$this->assertEquals(1, $callBackCount);

		$localizedPart->unsetProperties();
		$this->assertNull($localizedPart->getLDateTime());
		$this->assertEquals(1, $callBackCount);


		$instance->getDocumentModel()->getProperty('lDateTime')->setDefaultValue($date2);
		$instance->resetCurrentLocalized();
		$localizedPart = $instance->getRefLocalization();

		$this->assertEquals(new \DateTime($date2), $localizedPart->getLDateTime());
		$this->assertEquals(1, $callBackCount);
		$this->assertFalse($localizedPart->isModified());

		$localizedPart->isModified(true);
		$dbData = $instance->dbData();
		$this->assertEquals(1, $callBackCount);
		$this->assertEquals($date2, $dbData['_LCID'][0]['lDateTime']);

		$localizedPart->setLDateTime($date);
		$this->assertEquals(2, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->getDocumentModel()->getProperty('lDateTime')->setDefaultValue(null);
		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(2, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals(new \DateTime($date2), $localizedPart->getLDateTime());

		$instance->cleanUp();
	}


	/**
	 * @depends testLocalizedDateTimeProperty
	 */
	public function testLocalizedDocIdProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertSame(0, $localizedPart->getLDocId());

		$doc101 = $this->getNewReadonlyDocument('Project_Tests_Basic', 101);

		$this->assertSame($localizedPart, $localizedPart->setLDocId($doc101));
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);

		$this->assertEquals(101, $localizedPart->getLDocId());

		$localizedPart->setLDocId($doc101);
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLDocId(101);
		$this->assertEquals(1, $callBackCount);

		$localizedPart->unsetProperties();
		$this->assertSame(0, $localizedPart->getLDocId());
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLDocId(null);
		$this->assertEquals(1, $callBackCount);

		$localizedPart->setLDocId(102);
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals(102, $dbData['_LCID'][0]['lDocId']);

		$localizedPart->setLDocId(101);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals(102, $localizedPart->getLDocId());

		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedDocIdProperty
	 */
	public function testLocalizedJSONProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$this->assertNull($localizedPart->getLJSON());

		$json1 = ['test' => 0, 'test2' => true, 'Test3' => ['v1', 'v2']];

		$this->assertSame($localizedPart, $localizedPart->setLJSON($json1));
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);

		$this->assertEquals($json1, $localizedPart->getLJSON());

		$localizedPart->setLJSON($json1);
		$this->assertEquals(1, $callBackCount);

		$localizedPart->unsetProperties();
		$this->assertNull($localizedPart->getLJSON());
		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLJSON(null);
		$this->assertEquals(1, $callBackCount);

		$json2 = ['test' => 4, 'test2' => false, 'Test3' => ['v1', 'v2']];
		$localizedPart->setLJSON($json2);
		$this->assertEquals(2, $callBackCount);
		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals($json2, $dbData['_LCID'][0]['lJSON']);

		$localizedPart->setLJSON($json1);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals($json2, $localizedPart->getLJSON());

		$localizedPart->setLJSON(null);
		$this->assertNull($localizedPart->getLJSON());

		$localizedPart->setLJSON([]);
		$this->assertNull($localizedPart->getLJSON());

		$instance->cleanUp();
	}

	/**
	 * @depends testLocalizedJSONProperty
	 */
	public function testLocalizedRichTextProperty()
	{
		$documentManager = $this->getApplicationServices()->getDocumentManager();
		/** @var $instance \ChangeTests\Generators\Documents\InlineCompile */
		$instance = $documentManager->getNewInlineInstanceByModelName('ChangeTests_Generators_InlineCompile');

		$refLCID = $this->getApplicationServices()->getDocumentManager()->getLCID();
		$instance->setRefLCID($refLCID);

		$callBackCount = 0;
		$instance->link(function() use (&$callBackCount) {$callBackCount++;});
		$localizedPart = $instance->getRefLocalization();
		$richtextProperty = $localizedPart->getLRichText();
		$this->assertInstanceOf('\Change\Documents\RichtextProperty', $richtextProperty);
		$this->assertTrue($richtextProperty->isEmpty());

		$richtext1 = new \Change\Documents\RichtextProperty();
		$richtext1->setEditor('Html')->setRawText('Dummy text');

		$richtext2 = new \Change\Documents\RichtextProperty();
		$richtext2->setEditor('Html')->setRawText('Dummy text 2');

		$this->assertSame($localizedPart, $localizedPart->setLRichText($richtext1));
		$this->assertTrue($localizedPart->isNew());
		$this->assertTrue($localizedPart->isModified());
		$this->assertFalse($localizedPart->isEmpty());
		$this->assertEquals(1, $callBackCount);

		$this->assertEquals($richtext1->toArray(), $localizedPart->getLRichText()->toArray());

		$localizedPart->setLRichText($richtext1->toArray());
		$this->assertEquals(1, $callBackCount);

		$localizedPart->unsetProperties();
		$richtextProperty = $localizedPart->getLRichText();
		$this->assertTrue($richtextProperty->isEmpty());

		$this->assertEquals(1, $callBackCount);
		$localizedPart->setLRichText(null);
		$this->assertEquals(1, $callBackCount);

		$localizedPart->setLRichText($richtext2);
		$this->assertEquals(2, $callBackCount);

		$dbData = $instance->dbData();
		$this->assertEquals(2, $callBackCount);
		$this->assertEquals($richtext2->toArray(), $dbData['_LCID'][0]['lRichText']);

		$localizedPart->setLRichText($richtext1);
		$this->assertEquals(3, $callBackCount);
		$this->assertTrue($localizedPart->isModified());

		$instance->dbData($dbData);
		$this->assertNotSame($localizedPart, $instance->getRefLocalization());
		$localizedPart = $instance->getRefLocalization();
		$this->assertEquals(3, $callBackCount);
		$this->assertFalse($localizedPart->isModified());
		$this->assertEquals($richtext2->toArray(), $localizedPart->getLRichText()->toArray());

		$instance->cleanUp();
	}
}