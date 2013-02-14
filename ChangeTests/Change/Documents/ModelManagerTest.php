<?php
namespace ChangeTests\Change\Documents;

class ModelManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Documents\ModelManager
	 */
	public function testInitialize()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$compiler->generate();

		$modelManager = $this->getDocumentServices()->getModelManager();
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
