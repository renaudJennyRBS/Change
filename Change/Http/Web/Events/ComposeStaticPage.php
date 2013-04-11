<?php
namespace Change\Http\Web\Events;

use Change\Http\Web\Result\HtmlHeaderElement;
use Change\Http\Web\Result\Page as PageResult;
use Change\Website\Documents\StaticPage;
use Change\Http\Web\Blocks\Manager as BlocksManager;

/**
 * @name \Change\Http\Web\Events\ComposeStaticPage
 */
class ComposeStaticPage extends ComposePage
{
	/**
	 * Use Required Event Params: page
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$staticPage = $event->getParam('page');
		if ($staticPage instanceof StaticPage)
		{
			$this->setEventManager($staticPage->getEventManager());

			$pageEvent = $this->newPageEvent($event, $staticPage);
			$this->dispatchPrepare($pageEvent);

			$pageResult = $pageEvent->getPageResult();
			if ($pageResult instanceof PageResult)
			{
				$base = $event->getUrlManager()->getByPathInfo(null)->normalize()->toString();
				$headElement = new HtmlHeaderElement('base', array('href' => $base, 'target' => '_self'));
				$pageResult->addNamedHeadAsString('base', $headElement);

				$application = $event->getApplicationServices()->getApplication();
				$blocks = $this->extractBlocks($pageResult);

				if (count($blocks))
				{
					$blockManager = new BlocksManager($application);
					$blockResults = $this->generateBlockResults($blocks, $blockManager);
					$pageResult->setBlockResults($blockResults);
				}

				$this->dispatchCompose($pageEvent);

				$cachePath = $application->getWorkspace()->cachePath('twig', 'compilation');

				$callable = function () use ($pageResult, $cachePath)
				{
					$loader = new \Twig_Loader_String();
					$twig = new \Twig_Environment($loader, array('cache' => $cachePath));
					return $twig->render($pageResult->getHtmlTemplate(), array('pageResult' => $pageResult));
				};

				$pageResult->setRenderer($callable);

				$event->setResult($pageResult);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param PageEvent $pageEvent
	 * @throws \RuntimeException
	 */
	public function onPrepare($pageEvent)
	{
		parent::onPrepare($pageEvent);
		if (($result = $pageEvent->getPageResult()) !== null && (($staticPage = $pageEvent->getPage()) instanceof StaticPage))
		{
			/* @var $staticPage StaticPage */
			$headElement = new HtmlHeaderElement('title');
			$headElement->setContent('Page: ' . $staticPage->getNavigationTitle());
			$result->addNamedHeadAsString('title', $headElement);
		}
	}
}