<?php
namespace ChangeTests\Rbs\Elasticsearch;

use Rbs\Elasticsearch\ElasticsearchServices;

/**
 * @name \ChangeTests\Rbs\Elasticsearch\ElasticsearchServicesTest
 */
class ElasticsearchServicesTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var ElasticsearchServices
	 */
	protected $elasticsearchServices;

	protected function setUp()
	{
		$this->elasticsearchServices = new ElasticsearchServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('Rbs\Elasticsearch\ElasticsearchServices', $this->elasticsearchServices);
		$names = $this->elasticsearchServices->getIndexManager()->getClientsName();
		if (count($names) != 1)
		{
			$this->markTestSkipped('ElasticsearchServices not configured.');
		}

		try {
			$cli = $this->elasticsearchServices->getIndexManager()->getClient($names[0]);
			$status = $cli->getStatus();
			$infos = $status->getServerStatus();
			if (!isset($infos['ok']) || !$infos['ok'])
			{
				$this->markTestSkipped('elasticsearch server not ok.');
			}
		}
		catch (\Exception $e)
		{
			$this->markTestSkipped('elasticsearch not installed.');
		}
	}

	public function testGetInstance()
	{
		$this->assertInstanceOf('Rbs\Elasticsearch\ElasticsearchServices', $this->elasticsearchServices);
		$this->assertInstanceOf('Rbs\Elasticsearch\Index\IndexManager', $this->elasticsearchServices->getIndexManager());
		$this->assertInstanceOf('Rbs\Elasticsearch\Facet\FacetManager', $this->elasticsearchServices->getFacetManager());

		$names = $this->elasticsearchServices->getIndexManager()->getClientsName();
		$this->assertCount(1, $names);
		$this->assertEquals('front', $names[0]);
	}
}
