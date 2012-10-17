<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\ModelClassTest
 */
class ModelClassTest extends \PHPUnit_Framework_TestCase
{
	public function testAllType()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$definitionPath = __DIR__ . '/TestAssets/TestGeneration.xml';
		$model = $compiler->loadDocument('change', 'testing', 'generation', $definitionPath);
		$compiler->buildDependencies();
		
		$generator = new \Change\Documents\Generators\ModelClass();	
		$code = $generator->getPHPCode($compiler, $model);
		
		//file_put_contents($definitionPath . '.model.php.expected', $code);
		$expected = file_get_contents($definitionPath . '.model.php.expected');
		
		$this->assertEquals($expected, $code);
	}
}