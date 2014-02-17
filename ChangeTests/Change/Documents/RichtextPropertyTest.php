<?php
namespace ChangeTests\Change\Documents;

use Change\Documents\RichtextProperty;

/**
* @name \ChangeTests\Change\Documents\RichtextPropertyTest
*/
class RichtextPropertyTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @param $default
	 * @return RichtextProperty
	 */
	protected function getObject($default = null)
	{
		return new RichtextProperty($default);
	}

	public function testConstruct()
	{
		$o = $this->getObject();
		$this->assertNull($o->getHtml());
		$this->assertNull($o->getRawText());
		$this->assertTrue($o->isEmpty());
		$this->assertFalse($o->isModified());
		$this->assertEquals(RichtextProperty::DEFAULT_EDITOR, $o->getEditor());
		$this->assertNull($o->toJSONString());
		$this->assertNull($o->getDefaultJSONString());
		$this->assertEquals(array('e' => 'Markdown', 't' => null, 'h' => null), $o->toArray());
	}

	public function testEditor()
	{
		$o = $this->getObject();
		$this->assertSame($o, $o->setEditor('Test'));
		$this->assertEquals('Test', $o->getEditor());
		$this->assertTrue($o->isModified());
		$this->assertNull($o->getDefaultJSONString());
		$o->setEditor(null);
		$this->assertFalse($o->isModified());
		$this->assertEquals(RichtextProperty::DEFAULT_EDITOR, $o->getEditor());
	}

	public function testRawText()
	{
		$o = $this->getObject();
		$this->assertSame($o,$o->setRawText('RawText'));
		$this->assertFalse($o->isEmpty());
		$this->assertTrue($o->isModified());
		$this->assertNull($o->getDefaultJSONString());
		$this->assertEquals('RawText', $o->getRawText());
		$o->setRawText(null);
		$this->assertFalse($o->isModified());
		$this->assertTrue($o->isEmpty());
	}

	public function testHtml()
	{
		$o = $this->getObject();
		$this->assertSame($o,$o->setHtml('Html'));
		$this->assertEquals('Html', $o->getHtml());
		$this->assertTrue($o->isModified());
		$this->assertNull($o->getDefaultJSONString());
		$o->setHtml(null);
		$this->assertFalse($o->isModified());
		$this->assertNull($o->getHtml());
	}

	public function testDefault()
	{
		$o = $this->getObject(array('e' => 'TEXT', 't' => 'tt', 'h' => 'hh'));
		$this->assertFalse($o->isModified());
		$this->assertEquals('{"e":"TEXT","t":"tt","h":"hh"}', $o->getDefaultJSONString());
		$this->assertEquals('tt', $o->getRawText());
		$o->setRawText('modified');
		$this->assertTrue($o->isModified());
		$this->assertEquals('{"e":"TEXT","t":"tt","h":"hh"}', $o->getDefaultJSONString());
		$this->assertEquals('{"e":"TEXT","t":"modified","h":"hh"}', $o->toJSONString());
		$o->setRawText('tt');
		$this->assertFalse($o->isModified());

		$o = $this->getObject('{"e":"TEXT","t":"tt","h":"hh"}');
		$this->assertFalse($o->isModified());
		$this->assertEquals('{"e":"TEXT","t":"tt","h":"hh"}', $o->getDefaultJSONString());
		$this->assertEquals('tt', $o->getRawText());
		$o->setRawText('modified');

		$o2 = $this->getObject($o);
		$this->assertNotSame($o, $o2);
		$this->assertFalse($o2->isModified());
		$this->assertEquals('{"e":"TEXT","t":"modified","h":"hh"}', $o2->getDefaultJSONString());
		$this->assertEquals('modified', $o2->getRawText());
		$o2->setRawText('original');
		$this->assertTrue($o2->isModified());
		$this->assertEquals('{"e":"TEXT","t":"modified","h":"hh"}', $o2->getDefaultJSONString());
		$o2->setAsDefault();
		$this->assertFalse($o2->isModified());
		$this->assertEquals('{"e":"TEXT","t":"original","h":"hh"}', $o2->getDefaultJSONString());
	}

	public function testArray()
	{
		$o = $this->getObject();
		$this->assertSame($o, $o->fromArray(array()));
		$this->assertEquals(array('e' => 'Markdown', 't' => null, 'h' => null), $o->toArray());

		$o->fromArray(array('e' => 'TEXT', 't' => 'tt', 'h' => 'hh'));
		$this->assertEquals('TEXT', $o->getEditor());
		$this->assertEquals('tt', $o->getRawText());
		$this->assertEquals('hh', $o->getHtml());
		$this->assertEquals(array('e' => 'TEXT', 't' => 'tt', 'h' => 'hh'), $o->toArray());

		$o->fromArray(array());
		$this->assertEquals(array('e' => 'Markdown', 't' => null, 'h' => null), $o->toArray());
	}

	public function testJSONString()
	{
		$o = $this->getObject();
		$this->assertSame($o, $o->fromJSONString('text'));
		$this->assertEquals('{"e":"Markdown","t":"text","h":null}', $o->toJSONString());
		$this->assertEquals('text', $o->getRawText());

		$o->fromJSONString('{"e":"TEXT","t":"tt","h":"hh"}');
		$this->assertEquals('TEXT', $o->getEditor());
		$this->assertEquals('tt', $o->getRawText());
		$this->assertEquals('hh', $o->getHtml());
		$this->assertEquals('{"e":"TEXT","t":"tt","h":"hh"}', $o->toJSONString());

		$o->fromJSONString(null);
		$this->assertNull($o->toJSONString());
	}
}