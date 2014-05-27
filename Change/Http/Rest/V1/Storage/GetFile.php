<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Storage;

use Change\Http\Rest\V1\ArrayResult;
use Change\Http\Rest\V1\CollectionResult;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Rest\V1\Storage\GetFile
 */
class GetFile
{
	/**
	 * Use Required Event Params: path
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$storagePath = $event->getParam('path');
		if (!$storagePath)
		{
			//Document Not Found
			return;
		}
		$storageManager =  $event->getApplicationServices()->getStorageManager();
		$itemInfo = $storageManager->getItemInfo($storagePath);
		if ($itemInfo && file_exists($storagePath))
		{
			$infos = parse_url($storagePath);
			if (!isset($infos['path'])) {$infos['path'] = '/';}

			$findPath = array(DIRECTORY_SEPARATOR, $infos['scheme'] . '://');
			$replacePath = array('/', 'storage/');

			$path = 'storage/' . $infos['host'] . $infos['path'];
			if ($itemInfo->isDir())
			{
				if (substr($storagePath, -1) !== '/')
				{
					$storagePath .= '/';
				}
				$result = new CollectionResult();
				$result->addLink(new \Change\Http\Rest\V1\Link($event->getUrlManager(), $path));
				$dirHandle = opendir($storagePath);
				$childNames = array();
				while (($name = readdir($dirHandle)) !== false)
				{
					if ($name !== '.' && $name !== '..')
					{
						$childNames[] = $name;
					}
				}
				closedir($dirHandle);
				$count = 0;
				foreach ($childNames as $name)
				{
					$childPath = $storagePath . $name;
					$fileInfo = $storageManager->getItemInfo($childPath);
					if ($fileInfo)
					{
						$count++;
						$restPath = str_replace($findPath, $replacePath, $fileInfo->getPathname());
						if ($fileInfo->isDir())
						{
							$restPath .= '/';
						}
						$link = new \Change\Http\Rest\V1\Link($event->getUrlManager(), $restPath);
						$res = array('name' => $fileInfo->getFilename(), 'link' => $link->toArray());
						$result->addResource($res);
					}
				}
				$result->setCount($count);
			}
			else
			{
				$link = new \Change\Http\Rest\V1\Link($event->getUrlManager(), $path);
				$hrefContent = $event->getUrlManager()->getByPathInfo($path)->setQuery(array('content' => 1))->normalize()->toString();
				$result = new ArrayResult();
				$content = $event->getRequest()->getQuery('content');
				if ($content)
				{
					$event->setParam('itemInfo', $itemInfo);
					$event->getController()->getEventManager()->attach(\Change\Http\Event::EVENT_RESPONSE, array($this, 'onResultContent'), 10);
				}
				$mTime = \DateTime::createFromFormat('U', $itemInfo->getMTime());
				$result->setArray(array(
					'link' => $link->toArray(),
					'storageURI' => $storagePath,
					'size' => $itemInfo->getSize(),
					'data' => $hrefContent,
					'mTime' => $mTime->format(\DateTime::ISO8601),
					'mimeType' => $itemInfo->getMimeType(),
					'publicURL' => $itemInfo->getPublicURL()));
			}
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$result->setHeaderLastModified(\DateTime::createFromFormat('U', $itemInfo->getMTime()));
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
			/* @var $result ArrayResult */
			$result = $event->getResult();
			$ra = $result->toArray();

			$response = $event->getController()->createResponse();

			if (!$event->getController()->resultNotModified($event->getRequest(), $result))
			{
				$response->setStatusCode(HttpResponse::STATUS_CODE_200);

				$response->getHeaders()->clearHeaders();

				if (isset($ra['mimeType']))
				{
					$result->getHeaders()->addHeaderLine('Content-Type: ' . $ra['mimeType']);
				}
				else
				{
					$result->getHeaders()->addHeaderLine('Content-Type:  application/octet-stream');
				}
				$response->getHeaders()->addHeaders($result->getHeaders());
				$response->setContent(file_get_contents($itemInfo->getPathname()));
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
