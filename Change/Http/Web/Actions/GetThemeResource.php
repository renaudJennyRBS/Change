<?php
namespace Change\Http\Web\Actions;

use Change\Http\Event;
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
				$event->getApplicationServices()->getLogging()->fatal(__METHOD__ . $themeResourcePath);
				$resource = $theme->getResource($themeResourcePath);
				if ($resource)
				{

					$result = new Resource($theme->getName() . '_' . $themeResourcePath);
					if ($resource->isValid())
					{
						$md = $resource->getModificationDate();
						$result->setHeaderLastModified($md);
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