<?php
namespace Rbs\Seo\Job;

/**
 * @name \Rbs\Seo\Job\GenerateSitemap
 */
class GenerateSitemap
{
	public function execute(\Change\Job\Event $event)
	{

		$website = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($event->getJob()->getArgument('websiteId'));
		/* @var $website \Rbs\Website\Documents\Website */
		$lcid = $event->getJob()->getArgument('LCID');
		$application = $event->getApplicationServices()->getApplication();

		//check if Seo directory already exist, if not, create it.
		if (!is_dir($this->getRbsSeoAssetFilePath($application)))
		{
			\Change\Stdlib\File::mkdir($this->getRbsSeoAssetFilePath($application));
		}

		$urlManager = $website->getUrlManager($lcid);
		$urlManager->setAbsoluteUrl(true);

		//TODO: work but find a better way
		//Create a special UrlManager to manage Assets URL.
		//Useful for the url path to robots.txt, the sitemap Index and sitemaps.
		$assetUrlManager = $website->getUrlManager($lcid);
		$assetUrlManager->setAbsoluteUrl(true);

		$resourceBaseUrl = $application->getConfiguration()->getEntry('Change/Install/webBaseURLPath') . '/Assets';
		$assetUrlManager->setScript($resourceBaseUrl . '/Rbs/Seo');

		$sitemapIndex = [];

		$model = $event->getDocumentServices()->getDocumentManager()->getModelManager()->getModelByName('Rbs_Seo_DocumentSeo');
		$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), $model);
		$dqb->addOrder('id');
		$qb = $dqb->dbQueryBuilder();
		$qb->addColumn($qb->getFragmentBuilder()->getDocumentColumn('id'));
		$query = $qb->query();
		$seoDocumentIds = $query->getResults($query->getRowsConverter()->addIntCol('document_id'));
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
				$seo = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($seoDocumentId);
				/* @var $seo \Rbs\Seo\Documents\DocumentSeo */
				$sitemapInfo = $seo->getSitemapGenerateForWebsites();
				if (array_key_exists($website->getId(), $sitemapInfo) && isset($sitemapInfo[$website->getId()]['generate']) &&
					$sitemapInfo[$website->getId()]['generate'])
				{
					$target = $seo->getTarget();
					/* @var $target \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable */

					$url = $xml->createElement('url');

					$documentUrl = $urlManager->getCanonicalByDocument($target, $website);
					//800 is the maximum number of characters composing an url because the norm fixes a file max size to 10MB.
					//And with 10000 urls of 800 characters, the file size is approximately 10MB
					if (strlen($documentUrl) > 800)
					{
						$documentUrl = $urlManager->getByPathInfoForWebsite($website, $lcid, $urlManager->getDefaultDocumentPathInfo($target, $website));
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

			$filename = 'sitemap.' . $website->getId() . '.' . $lcid . '.' . $loop . '.xml';
			$path = $this->getRbsSeoAssetFilePath($application, $filename);
			$xml->save($path);

			$sitemapIndex[] = [
				'loc' => $assetUrlManager->getByPathInfo($filename),
				'lastmod' => (new \DateTime())->format(\DateTime::W3C)
			];

			$loop++;
		}

		$application = $event->getApplicationServices()->getApplication();

		//create the sitemap index with all sitemaps
		$sitemapIndexFilename = $this->generateSitemapIndex($application, $sitemapIndex, $website->getId(), $lcid);

		//set robots.txt
		$this->generateRobotsTxt($application, $assetUrlManager, $website, $sitemapIndexFilename);

		$event->success();
	}

	/**
	 * @param \Change\Application $application
	 * @param array $sitemapIndex
	 * @param integer $websiteId
	 * @param string $lcid
	 * @return string
	 */
	protected function generateSitemapIndex($application, $sitemapIndex, $websiteId, $lcid)
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

		$filename = 'sitemap_index.' . $websiteId . '.' . $lcid . '.xml';
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