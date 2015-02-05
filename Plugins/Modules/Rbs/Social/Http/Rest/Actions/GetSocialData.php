<?php
namespace Rbs\Social\Http\Rest\Actions;

use Change\Http\Rest\V1\ArrayResult;

/**
 * @name \Rbs\Social\Http\Rest\Actions\GetSocialData
 */
class GetSocialData
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$documentId = $event->getRequest()->getQuery('documentId');
		if ($documentId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$document = $documentManager->getDocumentInstance($documentId);
			if ($document instanceof \Change\Documents\AbstractDocument && $document instanceof \Change\Documents\Interfaces\Publishable)
			{
				//TODO
				$socialManager = new \Rbs\Social\SocialManager();
				$socialManager->setDocumentManager($event->getApplicationServices()->getDocumentManager());

				$result->setArray($socialManager->getFormattedSocialData($documentId, $event->getApplicationServices()->getDbProvider(), $documentManager));
				$event->setResult($result);
			}
			else
			{
				$result->setArray([ 'error' => 'invalid document' ]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
				$event->setResult($result);
			}
		}
		else
		{
			$result->setArray([ 'error' => 'invalid document id' ]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			$event->setResult($result);
		}
	}
}