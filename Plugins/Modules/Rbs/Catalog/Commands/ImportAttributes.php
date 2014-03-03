<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Catalog\Commands\ImportAttributes
 */
class ImportAttributes
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$workspace = $event->getApplication()->getWorkspace();
		$filePath = $workspace->composeAbsolutePath($event->getParam('fileName'));
		if (!is_readable($filePath))
		{
			$response->addErrorMessage('Unable to read: ' . $filePath);
			return;
		}

		$json = json_decode(file_get_contents($filePath), true);
		if (!is_array($json) || !isset($json['documents']) || !is_array($json['documents']))
		{
			$response->addErrorMessage('Invalid json file: ' . $filePath);
			return;
		}

		$import = new \Rbs\Generic\Json\Import($applicationServices->getDocumentManager());
		$import->setDocumentCodeManager($applicationServices->getDocumentCodeManager());
		try
		{
			$applicationServices->getTransactionManager()->begin();
			$imported = $import->fromArray($json);
			$applicationServices->getTransactionManager()->commit();
			$response->addInfoMessage('Successfully imported ' . count($imported) . ' documents from file: ' . $filePath);
		}
		catch (\Exception $e)
		{
			$applicationServices->getTransactionManager()->rollBack($e);
			$response->addErrorMessage($e->getMessage());
		}
	}
}