<?php

namespace ChangeTests\Rbs\Seo\Http\Rest\Actions;

use Change\Http\Event;
use Change\Http\Request;

class GenerateSitemapTest extends \ChangeTests\Change\TestAssets\TestCase
{
	public static function setUpBeforeClass()
	{
		static::initDocumentsDb();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	public function testExecute()
	{
		$website = $this->getNewWebsite();

		$event = new Event();
		$event->setApplicationServices($this->getApplicationServices());
		$event->setDocumentServices($this->getDocumentServices());
		$paramArray = array('websiteId' => $website->getId(), 'LCID' => $website->getCurrentLCID());
		$event->setRequest((new Request())->setQuery(new \Zend\Stdlib\Parameters($paramArray)));
		$generateSitemap = new \Rbs\Seo\Http\Rest\Actions\GenerateSitemap();
		$generateSitemap->execute($event);
		$this->assertEquals(200, $event->getResult()->getHttpStatusCode());
		$result = $event->getResult();
		/* @var $result \Change\Http\Rest\Result\ArrayResult */
		$arrayResult = $result->toArray();
		$this->assertNotEmpty($arrayResult);
		$this->assertArrayNotHasKey('error', $arrayResult);
		$this->assertArrayHasKey('jobId', $arrayResult);
		$this->assertGreaterThan(0, $arrayResult['jobId']);

		$jobManager = new \Change\Job\JobManager();
		$jobManager->setApplicationServices($this->getApplicationServices());
		$jobManager->setDocumentServices($this->getDocumentServices());
		$job = $jobManager->getJob($arrayResult['jobId']);
		$jobArguments = $job->getArguments();
		$this->assertArrayHasKey('websiteId', $jobArguments);
		$this->assertEquals($website->getId(), $jobArguments['websiteId']);
		$this->assertArrayHasKey('LCID', $jobArguments);
		$this->assertEquals($website->getCurrentLCID(), $jobArguments['LCID']);
	}

	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
		/* @var $website \Rbs\Website\Documents\Website */
		$website->setLabel('Website');
		$website->getCurrentLocalization()->setTitle('Website');
		$website->setBaseurl('http://test.rbs.fr');

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$website->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $website;
	}
}