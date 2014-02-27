<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;

/**
 * @name \Rbs\Admin\Http\Actions\GetDocumentEditors
 */
class GetDocumentEditors
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		/* @var $manager \Rbs\Admin\Manager */
		$modules = $event->getApplicationServices()->getPluginManager()->getModules();
		$modelManager = $event->getApplicationServices()->getModelManager();
		$modelNames = $modelManager->getModelsNames();
		$editors = [];
		foreach ($modules as $module)
		{
			if ($module->isAvailable())
			{
				$editors[] = '';
				foreach ($modelNames as $modelName)
				{
					list($vendor, $moduleName, $document) = explode('_', $modelName);
					if ($vendor == $module->getVendor() && $moduleName == $module->getShortName())
					{
						$path = $module->getAssetsPath() . '/Admin/Documents/' . $document . '/';
						if (file_exists($path . '/editor.twig'))
						{
							$model = $modelManager->getModelByName($modelName);
							if ($model->isLocalized())
							{
								if (file_exists($path . '/editor.js') && file_exists($path . '/editor-translate.js'))
								{
								}
								elseif (file_exists($path . '/editor.js'))
								{
									$editors[] = '	__change.createEditorForModelTranslation(\'' . $modelName . '\');';
								}
								elseif (file_exists($path . '/editor-translate.js'))
								{
									$editors[] = '	__change.createEditorForModel(\'' . $modelName . '\');';
								}
								else
								{
									$editors[] = '	__change.createEditorsForLocalizedModel(\'' . $modelName . '\');';
								}
							}
							elseif (!file_exists($path . '/editor.js'))
							{
								$editors[] = '	__change.createEditorForModel(\'' . $modelName . '\');';
							}
						}
					}
				}
			}
		}

		$result = new \Rbs\Admin\Http\Result\Renderer();
		$result->setHeaderContentType('application/javascript');
		$result->setRenderer(function () use ($editors)
		{
			if (count($editors))
			{
				return '(function () {
	"use strict";
' . implode(PHP_EOL, $editors) . '
})();';
			}
			else
			{
				return '//No generic editor' . PHP_EOL;
			}
		});
		$event->setResult($result);
	}
}