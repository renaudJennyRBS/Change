<?php
namespace ChangeTests\Db\Schema;

use Change\Db\Schema\KeyDefinition;
use Change\Db\Schema\FieldDefinition;

class KeyDefinitionTest extends \PHPUnit_Framework_TestCase
{
	
	public function testConstruct()
	{
		$kd = new KeyDefinition();
		$this->assertEquals(KeyDefinition::INDEX, $kd->getType());
		$this->assertCount(0, $kd->getOptions());
		$this->assertNull($kd->getName());
		$this->assertNull($kd->getFields());
	}

	public function testName()
	{
		$kd = new KeyDefinition();
		$kd->setName('test2');
		$this->assertEquals('test2', $kd->getName());
	}

	public function testType()
	{
		$kd = new KeyDefinition(null);
		$this->assertTrue($kd->isIndex());
		$this->assertFalse($kd->isPrimary());
		$this->assertFalse($kd->isUnique());
		$kd->setType(KeyDefinition::PRIMARY);
		$this->assertEquals(KeyDefinition::PRIMARY, $kd->getType());
		$this->assertTrue($kd->isPrimary());
		$this->assertFalse($kd->isIndex());

		$kd->setType(KeyDefinition::UNIQUE);
		$this->assertTrue($kd->isUnique());
		$this->assertFalse($kd->isPrimary());
	}

	public function testOptions()
	{
		$kd = new KeyDefinition();
		$kd->setOptions(array('V1' => 'test1'));
		$this->assertCount(1, $kd->getOptions());

		$this->assertArrayHasKey('V1', $kd->getOptions());
		$this->assertEquals('test1', $kd->getOption('V1'));
		$this->assertNull($kd->getOption('V2'));

		$kd->setOption('V2', 'test2');
		$this->assertEquals('test2', $kd->getOption('V2'));
		$this->assertCount(2, $kd->getOptions());
	}

	public function testFields()
	{
		$kd = new KeyDefinition(null);
		$kd->setFields(array(new FieldDefinition('f1')));
		$this->assertCount(1, $kd->getFields());
		$r =  $kd->addField(new FieldDefinition('f2'));
		$this->assertSame($r, $kd);
		$this->assertCount(2, $kd->getFields());
	}
}
