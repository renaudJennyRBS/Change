<?php
/**
 * Copyright (C) 2014 Loic Couturier
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;
use Change\Http\Web\Result\Resource;

/**
 * @name \Rbs\Productreturn\Http\Web\ReturnSheet
 */
class ReturnSheet
{
	function __invoke()
	{
		if (func_num_args() === 1)
		{
			$event = func_get_arg(0);
			if ($event instanceof \Change\Http\Web\Event)
			{
				$this->execute($event);
			}
		}
	}

	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'GET')
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$documentId = $event->getRequest()->getQuery('documentId');
			$return = $documentManager->getDocumentInstance($documentId);
			if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
			{
				/** @var $media \Change\Documents\AbstractDocument */
				$em = $media->getEventManager();
				$docEvent = new \Change\Documents\Events\Event(static::EVENT_GET_DOWNLOAD_URI, $this, array('httpEvent' => $event));
				$em->trigger($docEvent);

				$downloadUri = $docEvent->getParam('downloadUri');
				if ($downloadUri != null)
				{
					$itemInfo = $event->getApplicationServices()->getStorageManager()->getItemInfo($downloadUri);
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

				$result->getHeaders()->addHeaderLine('Content-Type', 'application/force-download; name="' . basename($itemInfo->getPathname()) . '"');
				$result->getHeaders()->addHeaderLine('Content-Transfer-Encoding', 'binary');
				$result->getHeaders()->addHeaderLine('Content-Length', $itemInfo->getSize());
				$result->getHeaders()->addHeaderLine('Content-Disposition', 'attachment; filename="' . basename($itemInfo->getPathname()) . '"');
				$offset = 48 * 60 * 60;
				$result->getHeaders()->addHeaderLine('Expires', gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT");
				$result->getHeaders()->addHeaderLine('Cache-Control', 'no-cache, must-revalidate');
				$result->getHeaders()->addHeaderLine('Pragma', 'no-cache');
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