<?php

namespace ChangeTests\Rbs\Seo\Job;

class GenerateSitemapTest extends \ChangeTests\Change\TestAssets\TestCase
{
	/**
	 * @var boolean
	 */
	protected $seoFolderAlreadyExist;

	/**
	 * @var string[]
	 */
	protected $generatedFilePaths = [];

	public static function setUpBeforeClass()
	{
		$appServices = static::initDocumentsDb();
		$schema = new \Rbs\Catalog\Setup\Schema($appServices->getDbProvider()->getSchemaManager());
		$schema->generate();
	}

	public static function tearDownAfterClass()
	{
			static::clearDB();
	}

	protected function attachSharedListener(\Zend\EventManager\SharedEventManager $sharedEventManager)
	{
		parent::attachSharedListener($sharedEventManager);
		$this->attachCommerceServicesSharedListener($sharedEventManager);
	}

	protected function setUp()
	{
		parent::setUp();
		$this->initServices($this->getApplication());
		$this->seoFolderAlreadyExist = is_dir($this->getAssetSeoPath());
	}

	public function tearDown()
	{
		//delete generated files
		foreach ($this->generatedFilePaths as $generatedFilePath)
		{
			unlink($generatedFilePath);
		}

		//if this test created the Seo folder, delete it
		if (!$this->seoFolderAlreadyExist)
		{
			\Change\Stdlib\File::rmdir($this->getAssetSeoPath());
		}
		parent::tearDown();
	}

	public function testExecute()
	{
		//declare a job manager listener for this test suit
		$this->getApplication()->getConfiguration()->addVolatileEntry('Change/Events/JobManager/Rbs_Generic', '\\Rbs\\Generic\\Events\\JobManager\\Listeners');

		$website = $this->getNewWebsite();
		$websiteId = $website->getId();
		$LCID = $website->getCurrentLCID();
		$documentSeo = $this->getNewDocumentSeo($website);
		$urlManager = $website->getUrlManager($LCID);
		$urlManager->setPathRuleManager($this->getApplicationServices()->getPathRuleManager());
		$urlManager->setAbsoluteUrl(true);

		//we need to configure website to generate sitemap, that will create his own job, but that doesn't matter (we don't run it)
		$website->setSitemapGeneration(true);
		$website->setSitemaps([ ['LCID' => $website->getCurrentLCID(), 'timeInterval' => 'P0Y0M0W1DT0H0M0S'] ]);
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

		$jm = $this->getApplicationServices()->getJobManager();
		$randomKey = \Change\Stdlib\String::random();
		$job = $jm->createNewJob('Rbs_Seo_GenerateSitemap', [
			'websiteId' => $websiteId,
			'LCID' => $LCID,
			'timeInterval' => 'P0Y0M0W1DT0H0M0S',
			'randomKey' => $randomKey
		]);

		$jm->run($job);
		$this->assertEquals('waiting', $job->getStatus());
		//website has been updated, so we reload it
		$dqb = $this->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Website_Website');
		$website = $dqb->getFirstDocument();
		/* @var $website \Rbs\Website\Documents\Website */

		//robots.txt part TODO: see RBSChange/evolutions#33
		/*
		$robotsTxtFilePath = $this->getAssetSeoPath('robots.' . $websiteId . '.txt');
		$this->assertFileExists($robotsTxtFilePath);
		$this->generatedFilePaths['robotsTxt'] = $robotsTxtFilePath;
		$robotsTxt = \Change\Stdlib\File::read($robotsTxtFilePath);
		$this->assertNotEmpty($robotsTxt);
		$this->assertRegExp('/Sitemap: *./', $robotsTxt);
		$sitemapIndexUrl = explode(' ', $robotsTxt)[1];
		*/

		//find sitemap index URL in website sitemaps info
		$sitemapIndexUrl = null;

		foreach ($website->getSitemaps() as $sitemap)
		{
			if ($sitemap['LCID'] === $website->getCurrentLCID())
			{
				$sitemapIndexUrl = isset($sitemap['url']) ? $sitemap['url'] : null;
			}
		}
		$this->assertNotNull($sitemapIndexUrl, 'sitemap index url cannot be null, this value has to be set on website sitemaps info');

		//sitemapIndex part
		$sitemapIndexFilenameInUrl = substr(strrchr($sitemapIndexUrl, '/'), 1);
		$this->assertEquals('sitemap_index.' . $websiteId . '.' . $LCID . '.' . $randomKey . '.xml', $sitemapIndexFilenameInUrl);
		$sitemapIndexPath = $this->getAssetSeoPath($sitemapIndexFilenameInUrl);

		$this->assertFileExists($sitemapIndexPath);
		$this->generatedFilePaths['sitemapIndex'] = $sitemapIndexPath;
		$sitemapIndex = \Change\Stdlib\File::read($sitemapIndexPath);
		$this->assertNotEmpty($sitemapIndex);
		$sitemapIndexXml = new \DOMDocument();
		$sitemapIndexXml->loadXML($sitemapIndex);
		$this->assertEquals(1, $sitemapIndexXml->getElementsByTagName('sitemapindex')->length);
		$this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9', $sitemapIndexXml->getElementsByTagName('sitemapindex')->item(0)->namespaceURI);
		$this->assertEquals(1, $sitemapIndexXml->getElementsByTagName('sitemap')->length);
		$sitemapNode = $sitemapIndexXml->getElementsByTagName('sitemap')->item(0);

		$this->assertEquals($sitemapNode->parentNode, $sitemapIndexXml->documentElement);
		$this->assertEquals(1, $sitemapIndexXml->getElementsByTagName('loc')->length);
		$locUrl = $sitemapIndexXml->getElementsByTagName('loc')->item(0)->textContent;
		$this->assertNotEmpty($locUrl);

		$this->assertEquals(1, $sitemapIndexXml->getElementsByTagName('lastmod')->length);
		$lastmod = $sitemapIndexXml->getElementsByTagName('lastmod')->item(0)->textContent;
		$lastmodDate = \Datetime::createFromFormat(\DateTime::W3C, $lastmod);
		//Comparing timestamp, with 10s delta
		$this->assertEquals((new \DateTime())->getTimestamp(), $lastmodDate->getTimestamp(), 'date of last modification has to be approximately equal to now (comparing timestamp, with 10s delta)', 10);

		//sitemap part
		$sitemapFilenameInUrl = substr(strrchr($locUrl, '/'), 1);
		$this->assertEquals('sitemap.' . $websiteId . '.' . $LCID . '.Rbs_Catalog_Product' . '.1.' . $randomKey . '.xml',
			$sitemapFilenameInUrl);
		$sitemapPath = $this->getAssetSeoPath($sitemapFilenameInUrl);
		$this->assertFileExists($sitemapPath);
		$this->generatedFilePaths['sitemap'] = $sitemapPath;
		$sitemap = \Change\Stdlib\File::read($sitemapPath);
		$this->assertNotEmpty($sitemap);
		$sitemapXml = new \DOMDocument();
		$sitemapXml->loadXML($sitemap);
		$this->assertEquals(1, $sitemapXml->getElementsByTagName('urlset')->length);
		$this->assertEquals('http://www.sitemaps.org/schemas/sitemap/0.9',
			$sitemapXml->getElementsByTagName('urlset')->item(0)->namespaceURI);
		$this->assertEquals(1, $sitemapXml->getElementsByTagName('url')->length);
		$this->assertEquals(1, $sitemapXml->getElementsByTagName('loc')->length);
		$this->assertEquals($urlManager->getCanonicalByDocument($documentSeo->getTarget(), $website)->normalize()->toString(),
			$sitemapXml->getElementsByTagName('loc')->item(0)->textContent);
		$this->assertEquals(1, $sitemapXml->getElementsByTagName('lastmod')->length);
		$expectedLastmod = $documentSeo->getTarget()->getDocumentModel()->getProperty('modificationDate')->getValue($documentSeo->getTarget());
		$this->assertInstanceOf('\DateTime', $expectedLastmod);
		/* @var $expectedLastmod \Datetime */
		$this->assertEquals($expectedLastmod->format(\DateTime::W3C), $sitemapXml->getElementsByTagName('lastmod')->item(0)->textContent);
		$this->assertEquals(1, $sitemapXml->getElementsByTagName('changefreq')->length);
		$this->assertEquals($documentSeo->getSitemapChangeFrequency(), $sitemapXml->getElementsByTagName('changefreq')->item(0)->textContent);
		$this->assertEquals(1, $sitemapXml->getElementsByTagName('priority')->length);
		$this->assertEquals($documentSeo->getSitemapPriority(), $sitemapXml->getElementsByTagName('priority')->item(0)->textContent);
	}

	/**
	 * @return \Rbs\Website\Documents\Website
	 * @throws \Exception
	 */
	protected function getNewWebsite()
	{
		$website = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_Website');
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

	/**
	 * @param $website \Rbs\Website\Documents\Website
	 * @return \Rbs\Seo\Documents\DocumentSeo
	 * @throws \Exception
	 */
	protected function getNewDocumentSeo($website)
	{
		$documentSeo = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Seo_DocumentSeo');
		/* @var $documentSeo \Rbs\Seo\Documents\DocumentSeo */
		$documentSeo->setTarget($this->getNewProduct($website));
		$documentSeo->setSitemapChangeFrequency('monthly');
		$documentSeo->setSitemapPriority(0.5);
		$sitemapGenerateForWebsites[$website->getId()] = [
			'label' => $website->getLabel(),
			'generate' => true
		];
		$documentSeo->setSitemapGenerateForWebsites($sitemapGenerateForWebsites);

		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$documentSeo->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $documentSeo;
	}

	/**
	 * @param \Rbs\Website\Documents\Website $website
	 * @throws \Exception
	 * @return \Rbs\Catalog\Documents\Product
	 */
	protected function getNewProduct($website)
	{
		$product = $this->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Catalog_Product');
		/* @var $product \Rbs\Catalog\Documents\Product */
		$product->setLabel('Card');
		$product->getCurrentLocalization()->setTitle('Card');
		$product->getPublicationSections()->add($website);
		$tm = $this->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$product->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		return $product;
	}

	/**
	 * @param string $filename
	 * @return string
	 */
	protected function getAssetSeoPath($filename = null)
	{
		$workspace =  $this->getApplication()->getWorkspace();
		$webBaseDirectory = $this->getApplication()->getConfiguration()->getEntry('Change/Install/webBaseDirectory');
		$robotsTxtFilePath = $workspace->composeAbsolutePath($webBaseDirectory, 'Assets', 'Rbs', 'Seo', $filename);
		return $robotsTxtFilePath;
	}
}