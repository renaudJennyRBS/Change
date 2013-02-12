<?php
namespace ChangeTests\Db\Schema;

use Change\Db\Schema\FieldDefinition;

class FieldDefinitionTest extends \PHPUnit_Framework_TestCase
{
	
	public function testConstruct()
	{
		$fd = new FieldDefinition('test');
		$this->assertEquals('test', $fd->getName());
		$this->assertEquals(FieldDefinition::VARCHAR, $fd->getType());
		$this->assertCount(0, $fd->getOptions());
		$this->assertNull($fd->getDefaultValue());
		$this->assertTrue($fd->getNullable());
	}

	public function testName()
	{
		$fd = new FieldDefinition(null);
		$fd->setName('test2');
		$this->assertEquals('test2', $fd->getName());
	}

	public function testType()
	{
		$fd = new FieldDefinition(null);
		$fd->setType(FieldDefinition::DATE);
		$this->assertEquals(FieldDefinition::DATE, $fd->getType());
	}

	public function testDefaultValue()
	{
		$fd = new FieldDefinition(null);
		$fd->setDefaultValue('def');
		$this->assertEquals('def', $fd->getDefaultValue());
	}

	public function testNullable()
	{
		$fd = new FieldDefinition(null);
		$fd->setNullable(false);
		$this->assertFalse($fd->getNullable());
	}

	public function testOptions()
	{
		$fd = new FieldDefinition(null);
		$fd->setOptions(array('V1' => 'test1'));
		$this->assertCount(1, $fd->getOptions());

		$this->assertArrayHasKey('V1', $fd->getOptions());
		$this->assertEquals('test1', $fd->getOption('V1'));
		$this->assertNull($fd->getOption('V2'));

		$fd->setOption('V2', 'test2');
		$this->assertEquals('test2', $fd->getOption('V2'));
		$this->assertCount(2, $fd->getOptions());
	}

	public function testLength()
	{
		$fd = new FieldDefinition(null);
		$fd->setLength(255);
		$this->assertCount(1, $fd->getOptions());
		$this->assertEquals(255, $fd->getLength());
	}

	public function testCharset()
	{
		$fd = new FieldDefinition(null);
		$fd->setCharset('UTF-8');
		$this->assertCount(1, $fd->getOptions());
		$this->assertEquals('UTF-8', $fd->getCharset());
	}

	public function testCollation()
	{
		$fd = new FieldDefinition(null);
		$fd->setCollation('general');
		$this->assertCount(1, $fd->getOptions());
		$this->assertEquals('general', $fd->getCollation());
	}

	public function testAutoNumber()
	{
		$fd = new FieldDefinition(null);
		$fd->setAutoNumber(true);
		$this->assertCount(1, $fd->getOptions());
		$this->assertTrue($fd->getAutoNumber());
	}

	public function testPrecision()
	{
		$fd = new FieldDefinition(null);
		$fd->setPrecision(15);
		$this->assertCount(1, $fd->getOptions());
		$this->assertEquals(15, $fd->getPrecision());
	}

	public function testScale()
	{
		$fd = new FieldDefinition(null);
		$fd->setScale(4);
		$this->assertCount(1, $fd->getOptions());
		$this->assertEquals(4, $fd->getScale());
	}
}
