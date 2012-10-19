<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\DocumentI18nClassTest
 */
class DocumentI18nClassTest extends \PHPUnit_Framework_TestCase
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
		$definitionPath = __DIR__ . '/TestAssets/TestGeneration.xml';
		$model = $compiler->loadDocument('change', 'testing', 'generation', $definitionPath);
		$compiler->buildDependencies();
		
		$generator = new \Change\Documents\Generators\DocumentI18nClass();
		$code = $generator->getPHPCode($compiler, $model);
		
		//file_put_contents($definitionPath . '.i18n.php.expected', $code);
		$expected = file_get_contents($definitionPath . '.i18n.php.expected');
				
		$this->assertEquals($expected, $code);
	}
}