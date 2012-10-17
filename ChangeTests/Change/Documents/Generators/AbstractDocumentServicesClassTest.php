<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\AbstractDocumentServicesClassTest
 */
class AbstractDocumentServicesClassTest extends \PHPUnit_Framework_TestCase
{
	
	public function testAllType()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		
		$models = array();
		$models[] = $compiler->loadDocument('change', 'testing', 'inject1',  __DIR__ . '/TestAssets/TestValidateInject1.xml');
		$models[] = $compiler->loadDocument('change', 'testing', 'inject2',  __DIR__ . '/TestAssets/TestValidateInject2.xml');
		$models[] = $compiler->loadDocument('change', 'testing', 'inject3',  __DIR__ . '/TestAssets/TestValidateInject3.xml');
		$compiler->buildDependencies();
		
		
		$generator = new \Change\Documents\Generators\AbstractDocumentServicesClass();
		$code = $generator->getPHPCode($compiler, $models);
		
		//file_put_contents(__DIR__ . '/TestAssets/AbstractDocumentServicesTest.php.expected', $code);
		$expected = file_get_contents(__DIR__ . '/TestAssets/AbstractDocumentServicesTest.php.expected');
				
		$this->assertEquals($expected, $code);
	}
}