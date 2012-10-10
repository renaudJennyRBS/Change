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
		$defModel = $compiler->getDefaultModel();
		$this->assertCount(13, $defModel->getProperties());
		
	}
	
	public function testLoadProjectDocuments()
	{
		$compiler = new \Change\Documents\Generators\Compiler();
		$compiler->loadProjectDocuments();
	}
}
