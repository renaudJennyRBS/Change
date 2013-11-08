<?php
namespace Rbs\Seo\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;

/**
 * @see http://www.sitemaps.org/protocol.html
 * @name \Rbs\Seo\Http\Rest\Actions\GenerateSitemap
 */
class GenerateSitemap
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$websiteId = $event->getRequest()->getQuery('websiteId');
		if ($websiteId)
		{
			$website = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($websiteId);
			if ($website instanceof \Rbs\Website\Documents\Website)
			{
				$lcid = $event->getRequest()->getQuery('LCID');
				if ($lcid && in_array($lcid, $website->getLCIDArray()))
				{
					$jm = $event->getApplicationServices()->getJobManager();
					$job = $jm->createNewJob('Rbs_Seo_GenerateSitemap', [ 'websiteId' => $websiteId, 'LCID' => $lcid ]);
					$result->setArray([ 'jobId' => $job->getId() ]);
				}
				else
				{
					$result->setArray([ 'error' => 'invalid LCID' ]);
					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
				}
			}
			else
			{
				$result->setArray([ 'error' => 'invalid website' ]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			}

			$event->setResult($result);
		}
		else
		{
			$result->setArray([ 'error' => 'invalid website id' ]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
		}
		$event->setResult($result);
	}
}