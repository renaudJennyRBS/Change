<?php
namespace Rbs\Seo\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;

/**
 * @name \Rbs\Seo\Http\Rest\Actions\GetMetaVariables
 */
class GetMetaVariables
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$targetId = $event->getRequest()->getQuery('targetId');
		if ($targetId)
		{
			$target = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($targetId);
			if ($target instanceof \Change\Documents\AbstractDocument)
			{
				$seoManager = new \Rbs\Seo\Services\SeoManager();
				$seoManager->setApplicationServices($event->getApplicationServices());
				$seoManager->setDocumentServices($event->getDocumentServices());
				$result->setArray($seoManager->getMetaVariables($target));
			}
			else
			{
				$result->setArray([ 'error' => 'invalid target' ]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			}
		}
		else
		{
			$result->setArray([ 'error' => 'invalid target id' ]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
		}

		$event->setResult($result);
	}
}