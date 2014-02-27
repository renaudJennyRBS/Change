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
use Change\Http\Web\Result\Resource;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Actions\GetAggregate
 */
class GetAggregate
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$resourcePath =  $event->getParam('resourcePath');
		$storageURI = 'change://tmp/'.$resourcePath;
		$storageManager = $event->getApplicationServices()->getStorageManager();
		$itemInfo = $storageManager->getItemInfo($storageURI);
		$result = new Resource($storageURI);
		if ($itemInfo->isReadable())
		{
			$md =\DateTime::createFromFormat('U', $itemInfo->getMTime());
			$result->setHeaderLastModified($md);
			$ifModifiedSince = $event->getRequest()->getIfModifiedSince();
			if ($ifModifiedSince && $ifModifiedSince == $md)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_304);
				$result->setRenderer(function ()
				{
					return null;
				});
			}
			else
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$result->getHeaders()->addHeaderLine('Content-Type', $itemInfo->getMimeType());
				$result->setRenderer(function () use ($itemInfo)
				{
					return file_get_contents($itemInfo->getPathname());
				});
			};
		}
		else
		{
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
			$result->setRenderer(function ()
			{
				return null;
			});

		}
		$event->setResult($result);
	}
}