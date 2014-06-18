<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Seo\Http\Rest\Actions;

use Change\Http\Rest\V1\ArrayResult;

/**
 * @name \Rbs\Seo\Http\Rest\Actions\GetVariables
 */
class GetVariables
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$modelName = $event->getRequest()->getQuery('modelName');
		if ($modelName)
		{
			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				$modelManager = $event->getApplicationServices()->getModelManager();
				$model = $modelManager->getModelByName($modelName);
				if ($model instanceof \Change\Documents\AbstractModel)
				{
					$seoManager = $genericServices->getSeoManager();
					$functions = array_merge($model->getAncestorsNames(), [$model->getName()]);
					$array = ['metaVariables' => $seoManager->getMetaVariables($functions),
						'pathVariables' => $seoManager->getPathVariables($model->getName()),
					];
					$result->setArray($array);
					$event->setResult($result);
				}
				else
				{
					$result->setArray([ 'error' => 'model: ' . $modelName . ' not found' ]);
					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
					$event->setResult($result);
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
			$result->setArray([ 'error' => 'invalid model name' ]);
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_500);
			$event->setResult($result);
		}
	}
}