<?php
namespace ChangeTests\Documents\Generators;

/**
 * @name \ChangeTests\Documents\Generators\CompilerInlineTest
 */
class CompilerInlineTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$this->assertCount(0, $compiler->getModels());
	}
	
	public function testLoadDocument()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$definitionPath = __DIR__ . '/TestAssets/TestInline.xml';
		$model = $compiler->loadDocument('Change', 'Test', 'TestInline', $definitionPath);
		$this->assertEquals('Change_Test_TestInline', $model->getName());
		$this->assertCount(3, $compiler->getModels());
		foreach ($compiler->getModels() as $name => $model)
		{
			$this->assertEquals($name, $model->getName());
			$names[] =  $name;
			if ($name == 'Change_Test_TestInline')
			{
				$this->assertNull($model->getInline());
			}
			else
			{
				$this->assertTrue($model->getInline());
			}
		}
	}
}
