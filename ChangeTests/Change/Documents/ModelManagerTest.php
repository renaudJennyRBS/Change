<?php
namespace ChangeTests\Change\Documents;

class ModelManagerTest extends \ChangeTests\Change\TestAssets\TestCase
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
		$application = $this->getApplication();
		$this->compileDocuments($application);
		$modelManager = $application->getDocumentServices()->getModelManager();
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
