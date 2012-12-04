<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\SchemaClassTest
 */
class SchemaClassTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return \Change\Application::getInstance();
	}
		
	public function testAllType()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication());
		
		$models = array();
		$models[] = $compiler->loadDocument('change', 'testing', 'inject1',  __DIR__ . '/TestAssets/TestValidateInject1.xml');
		$models[] = $compiler->loadDocument('change', 'testing', 'inject2',  __DIR__ . '/TestAssets/TestValidateInject2.xml');
		$models[] = $compiler->loadDocument('change', 'testing', 'inject3',  __DIR__ . '/TestAssets/TestValidateInject3.xml');
		$compiler->buildDependencies();
		
		$generator = new \Change\Documents\Generators\SchemaClass();
		$code = $generator->getPHPCode($compiler, $this->getApplication()->getApplicationServices()->getDbProvider()->getSchemaManager());
		
		//file_put_contents(__DIR__ . '/TestAssets/SchemaClassTest.php.expected', $code);
		$expected = file_get_contents(__DIR__ . '/TestAssets/SchemaClassTest.php.expected');
		
		$this->assertEquals($expected, $code);
	}
}