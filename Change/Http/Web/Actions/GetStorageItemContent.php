<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Web\Actions;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;
use Change\Http\Web\Result\Resource;

/**
 * @name \Change\Http\Web\Actions\GetStorageItemContent
 */
class GetStorageItemContent
{
	/**
	 * Use Required Event Params: changeURI
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$changeURI = $event->getParam('changeURI');
		if ($changeURI instanceof \Zend\Uri\Uri)
		{
			$itemInfo = $event->getApplicationServices()->getStorageManager()->getItemInfo($changeURI->toString());
			$result = new Resource($itemInfo->getPathname());
			if ($itemInfo)
			{
				$md = \DateTime::createFromFormat('U', $itemInfo->getMTime());
				$result->setHeaderLastModified($md);
				$result->getHeaders()->addHeaderLine('Cache-Control', 'public');
				$ifModifiedSince = $event->getRequest()->getIfModifiedSince();
				if ($ifModifiedSince && $ifModifiedSince == $md)
				{
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_304);
					$result->setRenderer(function() {return null;});
				}
				else
				{
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
					$contentType = $itemInfo->getMimeType();
					$result->getHeaders()->addHeaderLine('Content-Type', $contentType ? $contentType : 'application/octet-stream');
					$result->setRenderer(function() use ($itemInfo) {return file_get_contents($itemInfo->getPathname());});
				}
			}
			else
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
				$result->setRenderer(function() {return null;});
			}
			$event->setResult($result);
		}
	}
}