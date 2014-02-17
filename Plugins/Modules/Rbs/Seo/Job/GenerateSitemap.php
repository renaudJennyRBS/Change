<?php
namespace Rbs\Seo\Job;

/**
 * @see http://www.sitemaps.org/protocol.html
 * @name \Rbs\Seo\Job\GenerateSitemap
 */
class GenerateSitemap
{
	public function execute(\Change\Job\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$website = $applicationServices->getDocumentManager()->getDocumentInstance($event->getJob()->getArgument('websiteId'));

		/* @var $website \Rbs\Website\Documents\Website */
		$LCID = $event->getJob()->getArgument('LCID');
		$randomKey = $event->getJob()->getArgument('randomKey');
		$application = $event->getApplication();

		//check if Seo directory already exist, if not, create it.
		if (!is_dir($this->getRbsSeoAssetFilePath($application)))
		{
			\Change\Stdlib\File::mkdir($this->getRbsSeoAssetFilePath($application));
		}

		$urlManager = $website->getUrlManager($LCID);
		$urlManager->setPathRuleManager($applicationServices->getPathRuleManager());
		$urlManager->setAbsoluteUrl(true);

		//TODO: work but find a better way
		//Create a special UrlManager to manage Assets URL.
		//Useful for the url path to robots.txt, the sitemap Index and sitemaps.
		$assetUrlManager = $website->getUrlManager($LCID);
		$urlManager->setPathRuleManager($applicationServices->getPathRuleManager());
		$assetUrlManager->setAbsoluteUrl(true);

		$resourceBaseUrl = $application->getConfiguration()->getEntry('Change/Install/webBaseURLPath') . '/Assets';
		$assetUrlManager->setScript($resourceBaseUrl . '/Rbs/Seo');

		$sitemapIndex = [];

		$modelNames = $applicationServices->getModelManager()->getModelsNames();

		foreach ($modelNames as $modelName)
		{
			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Seo_DocumentSeo');
			$qb = $dqb->dbQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->addColumn($fb->column('document_id', $dqb->getTableAliasName()));
			$qb->innerJoin($fb->getDocumentIndexTable(),
				$fb->eq($fb->column('document_id', $fb->getDocumentIndexTable()),$fb->column('target', $dqb->getTableAliasName())));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('document_model', $fb->getDocumentIndexTable()), $fb->parameter('modelName'))
			));
			$sq = $qb->query();

			$sq->bindParameter('modelName', $modelName);

			$seoDocumentIds = $sq->getResults($sq->getRowsConverter()->addIntCol('document_id'));

			//create a sitemap for each 10000 urls
			$loop = 1;

			foreach(array_chunk($seoDocumentIds, 10000) as $seoDocumentIdsChunk)
			{
				$xml = new \DOMDocument('1.0', 'UTF-8');
				$xml->formatOutput = true;
				$urlset = $xml->createElement('urlset');
				$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
				$xml->appendChild($urlset);

				foreach ($seoDocumentIdsChunk as $seoDocumentId)
				{
					$seo = $applicationServices->getDocumentManager()->getDocumentInstance($seoDocumentId);
					/* @var $seo \Rbs\Seo\Documents\DocumentSeo */
					$sitemapInfo = $seo->getSitemapGenerateForWebsites();
					if (array_key_exists($website->getId(), $sitemapInfo) && isset($sitemapInfo[$website->getId()]['generate']) &&
						$sitemapInfo[$website->getId()]['generate'])
					{
						/* @var $target \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable */
						$target = $seo->getTarget();
						if (!($target instanceof \Change\Documents\Interfaces\Publishable) || !$target->getCanonicalSection($website))
						{
							continue;
						}

						$url = $xml->createElement('url');
						$documentUrl = $urlManager->getCanonicalByDocument($target, $website)->normalize()->toString();
						//800 is the maximum number of characters composing an url because the norm fixes a file max size to 10MB.
						//And with 10000 urls of 800 characters, the file size is approximately 10MB
						if (strlen($documentUrl) > 800)
						{
							$documentUrl = $urlManager->getByPathInfoForWebsite($website, $LCID, $urlManager->getDefaultDocumentPathInfo($target, $website))->normalize()->toString();
						}

						$loc = $xml->createElement('loc', $documentUrl);
						$url->appendChild($loc);

						$targetModificationDate = $target->getDocumentModel()->getProperty('modificationDate')->getValue($target);
						/* @var $targetModificationDate \Datetime */
						$lastmod = $xml->createElement('lastmod', $targetModificationDate->format(\Datetime::W3C));
						$url->appendChild($lastmod);

						$changefreq = $xml->createElement('changefreq', $seo->getSitemapChangeFrequency());
						$url->appendChild($changefreq);

						$priority = $xml->createElement('priority', $seo->getSitemapPriority());
						$url->appendChild($priority);

						$urlset->appendChild($url);
					}
				}

				$filename = 'sitemap.' . $website->getId() . '.' . $LCID . '.' . $modelName . '.' . $loop . '.' . $randomKey . '.xml';
				$path = $this->getRbsSeoAssetFilePath($application, $filename);
				$xml->save($path);

				$sitemapIndex[] = [
					'loc' => $assetUrlManager->getByPathInfo($filename)->toString(),
					'lastmod' => (new \DateTime())->format(\DateTime::W3C)
				];

				$loop++;
			}
		}

		$application = $event->getApplication();

		//create the sitemap index with all sitemaps
		$sitemapIndexFilename = $this->generateSitemapIndex($application, $sitemapIndex, $website->getId(), $LCID, $randomKey);

		//set robots.txt
		//TODO: generate a robots.txt? RBSChange/evolutions#33
		//$this->generateRobotsTxt($application, $assetUrlManager, $website, $sitemapIndexFilename);

		$jobSitemap = null;
		//update website document to set url for sitemap
		$sitemaps = [];
		foreach ($website->getSitemaps() as $sitemap)
		{
			if ($sitemap['LCID'] === $LCID)
			{
				//keep the sitemap for this job for future usage
				$jobSitemap = $sitemap;
				$sitemap['url'] = $assetUrlManager->getByPathInfo($sitemapIndexFilename)->toString();
			}
			$sitemaps[] = $sitemap;
		}
		$website->setSitemaps($sitemaps);
		$tm = $event->getApplicationServices()->getTransactionManager();
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

		//reschedule the job with time interval
		$reportDate = (new \DateTime())->add(new \DateInterval($jobSitemap['timeInterval']));
		$event->reported($reportDate);
	}

	/**
	 * @param \Change\Application $application
	 * @param array $sitemapIndex
	 * @param integer $websiteId
	 * @param string $LCID
	 * @param string $randomKey
	 * @return string
	 */
	protected function generateSitemapIndex($application, $sitemapIndex, $websiteId, $LCID, $randomKey)
	{
		$xml = new \DOMDocument('1.0', 'UTF-8');
		$xml->formatOutput = true;
		$sitemapindex = $xml->createElement('sitemapindex');
		$sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$xml->appendChild($sitemapindex);

		foreach ($sitemapIndex as $sitemapInfo)
		{
			$sitemap = $xml->createElement('sitemap');
			$loc = $xml->createElement('loc', $sitemapInfo['loc']);
			$sitemap->appendChild($loc);
			$lastmod = $xml->createElement('lastmod', $sitemapInfo['lastmod']);
			$sitemap->appendChild($lastmod);

			$sitemapindex->appendChild($sitemap);
		}

		$filename = 'sitemap_index.' . $websiteId . '.' . $LCID . '.' . $randomKey . '.xml';
		$path = $this->getRbsSeoAssetFilePath($application, $filename);
		$xml->save($path);

		return $filename;
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Http\Web\UrlManager $assetUrlManager
	 * @param \Rbs\Website\Documents\Website $website
	 * @param string $sitemapIndexFilename
	 */
	protected function generateRobotsTxt($application, $assetUrlManager, $website, $sitemapIndexFilename)
	{
		$robotsTxtPath = $path = $this->getRbsSeoAssetFilePath($application, 'robots.' . $website->getId() . '.txt');
		if (!file_exists($robotsTxtPath))
		{
			\Change\Stdlib\File::write($robotsTxtPath, '');
		}
		$robotsTxt = \Change\Stdlib\File::read($robotsTxtPath);
		$sitemap = 'Sitemap: ' . $assetUrlManager->getByPathInfo($sitemapIndexFilename);
		if (preg_match('/Sitemap: .*/', $robotsTxt))
		{
			$robotsTxt = preg_replace('/Sitemap: .*/', $sitemap,
				$robotsTxt);
		}
		else
		{
			//Add Sitemap info at the end of the file
			if (trim($robotsTxt) === '')
			{
				$robotsTxt = $sitemap;
			}
			else
			{
				$robotsTxt .= PHP_EOL . $sitemap;
			}
		}
		\Change\Stdlib\File::write($robotsTxtPath, $robotsTxt);
	}

	/**
	 * @param \Change\Application $application
	 * @param string $filename
	 * @return string
	 */
	protected function getRbsSeoAssetFilePath($application, $filename = null)
	{
		$workspace =  $application->getWorkspace();
		$webBaseDirectory = $application->getConfiguration()->getEntry('Change/Install/webBaseDirectory');
		$filePath = $workspace->composeAbsolutePath($webBaseDirectory, 'Assets', 'Rbs', 'Seo', $filename);
		return $filePath;
	}
}