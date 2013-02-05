<?php
namespace ChangeTests\Change\Documents;

class DocumentServicesTest extends \ChangeTests\Change\TestAssets\TestCase
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
	 * @return \Change\Documents\DocumentServices
	 */
	public function testInitialize()
	{
		$application = $this->getApplication();
		$this->compileDocuments($application);
		$documentsServices = $application->getDocumentServices();
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
