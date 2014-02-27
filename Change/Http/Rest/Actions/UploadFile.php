<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\Actions\UploadFile
 */
class UploadFile
{
	/**
	 * Use Required Event Params: file, destinationPath
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$file = $event->getParam('file');
		$destinationPath = $event->getParam('destinationPath');
		if (!is_array($file) || !is_string($destinationPath))
		{
			//Document Not Found
			return;
		}

		if (isset($file['tmp_name']))
		{
			if (move_uploaded_file($file['tmp_name'], $destinationPath))
			{
				$itemInfo = $event->getApplicationServices()->getStorageManager()->getItemInfo($destinationPath);
				if ($itemInfo)
				{
					$event->setParam('path', $destinationPath);
					$getFile = new GetFile();
					$getFile->execute($event);
				}
				else
				{
					throw new \RuntimeException('Unable to find: ' . $destinationPath, 999999);
				}
			}
			else
			{
				throw new \RuntimeException('Unable to move "' . $file['tmp_name'] . '" in "' . $destinationPath
					. '"', 999999);
			}
		}
	}
}
