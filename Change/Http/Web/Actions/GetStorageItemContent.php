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
				$event->setParam('itemInfo', $itemInfo);
				$event->getController()->getEventManager()->attach(\Change\Http\Event::EVENT_RESPONSE, array($this, 'onResultContent'), 10);
			}
			else
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
				$result->setRenderer(function() {return null;});
			}
			$event->setResult($result);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function onResultContent($event)
	{
		$itemInfo = $event->getParam('itemInfo');
		if ($itemInfo instanceof \Change\Storage\ItemInfo)
		{
			/* @var $result \Change\Http\Web\Result\Resource */
			$result = $event->getResult();

			$response = new \Change\Http\StreamResponse();

			if (!$event->getController()->resultNotModified($event->getRequest(), $result))
			{
				$response->setStatusCode(HttpResponse::STATUS_CODE_200);

				$response->getHeaders()->clearHeaders();

				$contentType = $itemInfo->getMimeType();
				$result->getHeaders()->addHeaderLine('Content-Type', $contentType ? $contentType : 'application/octet-stream');
				$response->getHeaders()->addHeaders($result->getHeaders());
				$response->setUri($itemInfo->getPathname());
			}
			else
			{
				$response->setStatusCode(HttpResponse::STATUS_CODE_304);
			}
			return $response;
		}
		return null;
	}
}