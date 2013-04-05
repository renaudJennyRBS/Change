<?php
namespace Change\Http\Web\Response;

use Change\Http\Web\Result\Page as PageResult;
use Change\Website\Documents\StaticPage;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Response\ComposeStaticPage
 */
class ComposeStaticPage
{
	/**
	 * Use Required Event Params: pathRule
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$staticPage = $event->getParam('page');
		if ($staticPage instanceof StaticPage)
		{
			$base = $event->getUrlManager()->getByPathInfo(null)->normalize()->toString();
			$result = new PageResult($staticPage->getId());
			$result->addNamedHeadAsString('title', '<title>Page: ' . $staticPage->getNavigationTitle() . '</title>');
			$result->addNamedHeadAsString('base', '<base href="' . $base . '" target="_self"><base>');
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
			$event->stopPropagation();
		}
	}
}