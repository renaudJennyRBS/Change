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
		$modelName = $event->getRequest()->getQuery('modelName');
		if ($modelName)
		{
			$modelManager = $event->getDocumentServices()->getDocumentManager()->getModelManager();
			$model = $modelManager->getModelByName($modelName);
			if ($model instanceof \Change\Documents\AbstractModel)
			{
				$seoManager = new \Rbs\Seo\Services\SeoManager();
				$seoManager->setApplicationServices($event->getApplicationServices());
				$seoManager->setDocumentServices($event->getDocumentServices());
				$functions = array_merge($model->getAncestorsNames(), [$model->getName()]);
				$result->setArray($seoManager->getMetaVariables($functions));
			}
			else
			{
				$result->setArray([ 'error' => 'model: ' . $modelName . ' not found' ]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			}
		}
		else
		{
			$result->setArray([ 'error' => 'invalid model name' ]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
		}

		$event->setResult($result);
	}
}