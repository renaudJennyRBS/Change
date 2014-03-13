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
use Change\Http\Web\Result\Resource;
use Change\Presentation\Interfaces\Theme;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\GetThemeResource
 */
class GetThemeResource
{
	/**
	 * Use Required Event Params: theme, themeResourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$theme = $event->getParam('theme');
		if ($theme instanceof Theme)
		{
			$themeResourcePath = $event->getParam('themeResourcePath');
			if (is_string($themeResourcePath))
			{
				$resource = $theme->getResource($themeResourcePath);
				if ($resource)
				{
					$result = new Resource($theme->getName() . '_' . $themeResourcePath);
					if ($resource->isValid())
					{
						if (substr($themeResourcePath, -5) === '.twig')
						{
							$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
							$result->getHeaders()->addHeaderLine('Content-Type', 'text/html');
							$templateManager = $event->getApplicationServices()->getTemplateManager();
							$result->setRenderer(function() use ($templateManager, $resource)
							{
								return $templateManager->renderTemplateString($resource->getContent(), []);
							});
						}
						else
						{
							$md = $resource->getModificationDate();
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
								$result->getHeaders()->addHeaderLine('Content-Type', $resource->getContentType());
								$result->setRenderer(function() use ($resource) {return $resource->getContent();});
							}
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
	}
}