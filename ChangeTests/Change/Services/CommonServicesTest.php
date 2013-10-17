<?php
namespace ChangeTests\Change\Services;

use Change\Services\CommonServices;

/**
* @name \ChangeTests\Change\Services\CommonServicesTest
*/
class CommonServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public function testConstruct()
	{
		$commonServices =  new CommonServices($this->getApplicationServices(),  $this->getDocumentServices());
		$this->assertInstanceOf('\Change\Services\CommonServices', $commonServices);
		$this->assertSame($this->getApplicationServices(), $commonServices->getApplicationServices());
		$this->assertSame($this->getDocumentServices(), $commonServices->getDocumentServices());
	}

	public function testGetCollectionManager()
	{
		$commonServices =  new CommonServices($this->getApplicationServices(),  $this->getDocumentServices());
		$this->assertInstanceOf('\Change\Collection\CollectionManager', $commonServices->getCollectionManager());
		$this->assertSame($this->getDocumentServices(), $commonServices->getCollectionManager()->getDocumentServices());
	}

	public function testGetJobManager()
	{
		$commonServices =  new CommonServices($this->getApplicationServices(),  $this->getDocumentServices());
		$this->assertInstanceOf('\Change\Job\JobManager', $commonServices->getJobManager());
		$this->assertSame($this->getApplicationServices(), $commonServices->getJobManager()->getApplicationServices());
		$this->assertSame($this->getDocumentServices(), $commonServices->getJobManager()->getDocumentServices());
	}
}