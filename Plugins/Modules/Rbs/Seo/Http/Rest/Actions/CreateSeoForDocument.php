<?php
namespace Rbs\Seo\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;

/**
 * @name \Rbs\Seo\Http\Rest\Actions\CreateSeoForDocument
 */
class CreateSeoForDocument
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$documentId = $event->getRequest()->getQuery('documentId');
		if ($documentId)
		{
			$genericService = $event->getServices('genericServices');
			if ($genericService instanceof \Rbs\Generic\GenericServices)
			{
				$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($documentId);
				if ($document)
				{
					$seoManager = $genericService->getSeoManager();
					$seo = $seoManager->createSeoDocument($document);

					if ($seo)
					{
						$event->setParam('documentId', $seo->getId());
						$event->setParam('modelName', $seo->getDocumentModelName());
						$action = new \Change\Http\Rest\Actions\GetDocument();
						$action->execute($event);
						return;
					}
					else
					{
						$result->setArray([ 'error' => 'invalid document and/or document is not publishable' ]);
						$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
						$event->setResult($result);
					}
				}
			}
			else
			{
				$result->setArray([ 'error' => 'invalid generic services' ]);
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