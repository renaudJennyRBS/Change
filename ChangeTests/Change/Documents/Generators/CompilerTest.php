<?php
namespace ChangeTests\Documents;

/**
 * @name \ChangeTests\Documents\Generators\CompilerTest
 */
class CompilerTest extends \PHPUnit_Framework_TestCase
{
	public function testConstruct()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		return $compiler;
	}

	/**
	 * @depends testConstruct
	 */	
	public function testGetDefaultModel(\Change\Documents\Generators\Compiler $compiler)
	{
		$defaultModel = $compiler->getDefaultModel();
	}
	
	public function testLoadProjectDocuments()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$compiler->loadProjectDocuments();
		
		$m = $compiler->getModelByFullName('modules_media/file');
		$this->assertNull($compiler->getParent($m));
		
		
		
		$result = $compiler->getDescendants($m);
		$this->assertArrayHasKey('modules_media/media', $result);
		$this->assertArrayHasKey('modules_media/tmpfile', $result);
		$this->assertArrayHasKey('modules_media/securemedia', $result);
				
		$result = $compiler->getChildren($m);
		$this->assertArrayHasKey('modules_media/media', $result);
		$this->assertArrayHasKey('modules_media/tmpfile', $result);
		$this->assertArrayNotHasKey('modules_media/securemedia', $result);
		
		$m = $compiler->getModelByFullName('modules_media/securemedia');
		$this->assertCount(0, $compiler->getChildren($m));
		$result = $compiler->getParent($m);
		$this->assertEquals('modules_media/media', $result);

		
		$result = $compiler->getAncestors($m);
		$this->assertArrayHasKey('modules_media/file', $result);
		$this->assertArrayHasKey('modules_media/media', $result);
		return $compiler;
	}
	
	/**
	 * @depends testLoadProjectDocuments
	 */	
	public function testSaveModelsPHPCode(\Change\Documents\Generators\Compiler $compiler)
	{
		$compiler->saveModelsPHPCode();
	}
}
