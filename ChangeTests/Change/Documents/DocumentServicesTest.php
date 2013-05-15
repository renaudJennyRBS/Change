<?php
namespace ChangeTests\Change\Documents;

class DocumentServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsClasses();
	}

	/**
	 * @return \Change\Documents\DocumentServices
	 */
	public function testInitialize()
	{
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
