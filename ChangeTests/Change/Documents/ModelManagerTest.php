<?php
namespace ChangeTests\Change\Documents;

class ModelManagerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param \Change\Application $application
	 */
	protected function compileDocuments(\Change\Application $application)
	{
		$compiler = new \Change\Documents\Generators\Compiler($application);
		$compiler->generate();
	}

	/**
	 * @return \Change\Documents\ModelManager
	 */
	public function testInitialize()
	{
		$application = \Change\Application::getInstance();
		$this->compileDocuments($application);
		$modelManager = \Change\Application::getInstance()->getDocumentServices()->getModelManager();
		$this->assertInstanceOf('\Change\Documents\ModelManager', $modelManager);
		return $modelManager;
	}

	/**
	 * @depends testInitialize
	 * @param \Change\Documents\ModelManager $modelManager
	 * @return \Change\Documents\ModelManager
	 */
	public function testGetModelByName($modelManager)
	{
		$m = $modelManager->getModelByName('Project_Tests_Basic');
		$this->assertInstanceOf('\Compilation\Project\Tests\Documents\BasicModel', $m);
		$this->assertEquals('Project_Tests_Basic', $m->getName());
		return $modelManager;
	}
}
