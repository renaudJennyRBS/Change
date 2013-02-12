<?php
namespace ChangeTests\Db\Schema;

use Change\Db\Schema\TableDefinition;
use Change\Db\Schema\FieldDefinition;
use Change\Db\Schema\KeyDefinition;

class TableDefinitionTest extends \PHPUnit_Framework_TestCase
{
	
	public function testConstruct()
	{
		$td = new TableDefinition('test');
		$this->assertEquals('test', $td->getName());
		$this->assertCount(0, $td->getOptions());
		$this->assertCount(0, $td->getFields());
		$this->assertCount(0, $td->getKeys());
		$this->assertFalse($td->isValid());
	}

	public function testName()
	{
		$td = new TableDefinition(null);
		$td->setName('test2');
		$this->assertEquals('test2', $td->getName());
	}
	public function testOptions()
	{
		$td = new TableDefinition('test');
		$td->setOptions(array('V1' => 'test1'));
		$this->assertCount(1, $td->getOptions());

		$this->assertArrayHasKey('V1', $td->getOptions());
		$this->assertEquals('test1', $td->getOption('V1'));
		$this->assertNull($td->getOption('V2'));

		$td->setOption('V2', 'test2');
		$this->assertEquals('test2', $td->getOption('V2'));
		$this->assertCount(2, $td->getOptions());
	}

	public function testFields()
	{
		$td = new TableDefinition('test');
		$td->setFields(array(new FieldDefinition('f1')));
		$this->assertCount(1, $td->getFields());
		$this->assertTrue($td->isValid());

		$r =  $td->addField(new FieldDefinition('f2'));
		$this->assertSame($r, $td);
		$this->assertCount(2, $td->getFields());
	}

	public function testKeys()
	{
		$td = new TableDefinition('test');
		$td->setKeys(array(new KeyDefinition()));
		$this->assertCount(1, $td->getKeys());

		$r =  $td->addKey(new KeyDefinition());
		$this->assertSame($r, $td);
		$this->assertCount(2, $td->getKeys());
	}

	public function testCharset()
	{
		$td = new TableDefinition(null);
		$td->setCharset('UTF-8');
		$this->assertCount(1, $td->getOptions());
		$this->assertEquals('UTF-8', $td->getCharset());
	}

	public function testCollation()
	{
		$td = new TableDefinition(null);
		$td->setCollation('general');
		$this->assertCount(1, $td->getOptions());
		$this->assertEquals('general', $td->getCollation());
	}
}
