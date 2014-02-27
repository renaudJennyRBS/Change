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
 * @name \Rbs\Admin\Http\Actions\GetResource
 */
class GetResource
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$resourcePath = $event->getParam('resourcePath');
		$result = new Resource($resourcePath);

		$filePath = $this->getFilePathByResourcePath($event->getParam('resourcePath'), $event->getApplicationServices()->getPluginManager());
		if ($filePath !== null)
		{
			$fileResource = new \Change\Presentation\Themes\FileResource($filePath);
			if ($fileResource->isValid())
			{
				$md = $fileResource->getModificationDate();
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
					$result->setHeaderExpires((new \DateTime())->add(new \DateInterval('P10D')));
					$result->getHeaders()->addHeaderLine('Content-Type', $fileResource->getContentType());
					$result->setRenderer(function () use ($fileResource)
					{
						return $fileResource->getContent();
					});
				}

				$event->setResult($result);
				return;
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
		$result->setRenderer(function ()
		{
			return null;
		});
		$event->setResult($result);
	}

	/**
	 * @param string $resourcePath
	 * @param \Change\Plugins\PluginManager $pluginManager
	 * @return string
	 */
	protected function getFilePathByResourcePath($resourcePath, $pluginManager)
	{
		$parts = explode('/', $resourcePath);
		if (count($parts) > 2)
		{
			$vendor = array_shift($parts);
			$shortModuleName = array_shift($parts);

			$plugin = $pluginManager->getModule($vendor, $shortModuleName);
			if ($plugin && $plugin->isAvailable())
			{
				if ($vendor === 'Rbs' && $shortModuleName === 'Admin')
				{
					return $plugin->getAssetsPath() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
				}
				else
				{
					return $plugin->getAssetsPath() . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR .
					implode(DIRECTORY_SEPARATOR, $parts);
				}
			}
		}
		return null;
	}
}