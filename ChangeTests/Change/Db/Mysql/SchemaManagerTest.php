<?php
namespace Tests\Db\Mysql;

class SqlMappingTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$sqlMapping = new \Change\Db\Mysql\SqlMapping();
		return $sqlMapping;
	}
	

	/**
	 * @depends testConstruct
	 */
	public function testFunctions(\Change\Db\Mysql\SqlMapping $sqlMapping)
	{
		$this->assertEquals('_i18n', $sqlMapping->getI18nSuffix());
		$this->assertEquals('`b`.`a` AS `c`', $sqlMapping->escapeName('a', 'b', 'c'));
		$this->assertEquals(':pa', $sqlMapping->escapeParameterName('a'));
		return $sqlMapping;
	}	
	
}
