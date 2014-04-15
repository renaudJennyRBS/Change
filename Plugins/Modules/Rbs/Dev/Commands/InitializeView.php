<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Dev\Commands\InitializeView
 */
class InitializeView
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$response = $event->getCommandResponse();

		$applicationServices = $event->getApplicationServices();
		$modelName = $event->getParam('modelName');
		$list = $event->getParam('list');
		$new = $event->getParam('new');
		$edit = $event->getParam('edit');
		$translate = $event->getParam('translate');
		$all = $event->getParam('all');

		$modelManager = $event->getApplicationServices()->getModelManager();
		$model = $modelManager->getModelByName($modelName);
		if (!$model)
		{
			$response->addErrorMessage($modelName . ' is not a valid model name.');
			return;
		}

		if ($all)
		{
			$views = ['list' => true, 'new' => true, 'edit' => true, 'translate' => $model->isLocalized()];
		}
		// At least one view has to be set.
		else if ($list | $new | $edit | $translate)
		{
			if ($translate === true && !$model->isLocalized())
			{
				$response->addWarningMessage('Model "' . $model->getName() . '" is not localized, the "translate" view will be not created');
			}
			$views = ['list' => $list === true, 'new' => $new === true, 'edit' => $edit === true, 'translate' => $translate === true && $model->isLocalized()];
		}
		else
		{
			$response->addErrorMessage('At least one view has to be set, you can add more than one with several arguments like "-l -e -f" or all with "--all"');
			return;
		}

		$genericServices = $event->getServices('genericServices');
		if ($genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$adminManager = $genericServices->getAdminManager();

			try
			{
				$filesGenerated = $adminManager->initializeView($model, $views);

				foreach ($filesGenerated['errors'] as $message)
				{
					$response->addErrorMessage($message);
				}
				foreach ($filesGenerated['paths'] as $view => $path)
				{
					$response->addInfoMessage(ucfirst($view) . ' view file written at path ' . $path);
				}
			}
			catch (\Exception $e)
			{
				$applicationServices->getLogging()->exception($e);
				$response->addErrorMessage($e->getMessage());
			}
		}
		else
		{
			$response->addErrorMessage('Generic services not found');
		}
	}
}