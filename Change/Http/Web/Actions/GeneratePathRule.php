<?php
namespace Change\Http\Web\Actions;

use Change\Documents\AbstractDocument;
use Change\Http\Web\PathRule;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Actions\GeneratePathRule
 */
class GeneratePathRule
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		/* @var $pathRule PathRule */
		$pathRule = $event->getPathRule();
		if (!($pathRule instanceof PathRule))
		{
			throw new \RuntimeException('Invalid Parameter: pathRule', 71000);
		}

		/* @var $urlManager \Change\Http\Web\UrlManager */
		$urlManager = $event->getUrlManager();
		$document = $event->getDocument();
		if ($document instanceof AbstractDocument)
		{
			$newPathRule = $urlManager->rewritePathRule($document, $pathRule);
			if ($newPathRule === null)
			{
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_200);
				$action = new DisplayDocument();
				$action->execute($event);
			}
			else
			{
				$pathRule->setHttpStatus(HttpResponse::STATUS_CODE_301);
				$urlManager->setAbsoluteUrl(true);
				$queryParameters = $pathRule->getQueryParameters();

				//Remove parameters stored in new pathRule
				$removeQueryParameters = $newPathRule->getQueryParameters();
				if (count($removeQueryParameters))
				{
					foreach ($removeQueryParameters as $name => $value)
					{
						if (isset($queryParameters[$name]) && $queryParameters[$name] == $value)
						{
							unset($queryParameters[$name]);
						}
					}
				}
				$uri = $urlManager->getByPathInfo($newPathRule->getRelativePath(), $queryParameters);
				$pathRule->setLocation($uri->normalize()->toString());
				$action = new RedirectPathRule();
				$action->execute($event);
			}
		}
	}
}
