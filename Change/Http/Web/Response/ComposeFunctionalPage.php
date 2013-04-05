<?php
namespace Change\Http\Web\Response;

use Change\Http\Web\Result\Page as PageResult;
use Change\Website\Documents\FunctionalPage;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Response\ComposeFunctionalPage
 */
class ComposeFunctionalPage
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$functionalPage = $event->getParam('page');
		if ($functionalPage instanceof FunctionalPage)
		{

			$result = new PageResult($functionalPage->getId());
			$result->addNamedHeadAsString('title', '<title>Page: ' . $functionalPage->getLabel() . '</title>');
			$base = $event->getUrlManager()->getByPathInfo(null)->normalize()->toString();
			$result->addNamedHeadAsString('base', '<base href="' . $base . '" target="_self"><base>');
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}