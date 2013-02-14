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
		return $documentsServices;
	}

	/**
	 * @depends testInitialize
	 * @param \Change\Documents\DocumentServices $documentsServices
	 * @return \Change\Documents\DocumentServices
	 */
	public function testGetModelByName($documentsServices)
	{
		$this->assertTrue(method_exists($documentsServices, 'getProjectTestsBasic'));
		$this->assertTrue(is_callable(array($documentsServices, 'getProjectTestsBasic')));

		$service = $documentsServices->getProjectTestsBasic();
		$this->assertInstanceOf('\Project\Tests\Documents\BasicService', $service);

		return $documentsServices;
	}
}
