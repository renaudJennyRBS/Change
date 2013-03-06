<?php
namespace ChangeTests\Change\Documents;

class DocumentServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function testInitialize()
	{
		$compiler = new \Change\Documents\Generators\Compiler($this->getApplication(), $this->getApplicationServices());
		$compiler->generate();

		$documentsServices = $this->getDocumentServices();
		$this->assertInstanceOf('\Change\Documents\DocumentServices', $documentsServices);
		$this->assertSame($this->getApplicationServices(), $documentsServices->getApplicationServices());

		$this->assertInstanceOf('\Change\Documents\ModelManager', $documentsServices->getModelManager());
		$this->assertInstanceOf('\Change\Documents\DocumentManager', $documentsServices->getDocumentManager());
		$this->assertInstanceOf('\Change\Documents\TreeManager', $documentsServices->getTreeManager());

		$this->assertInstanceOf('\Change\Documents\Constraints\ConstraintsManager', $documentsServices->getConstraintsManager());

		return $documentsServices;
	}
}
