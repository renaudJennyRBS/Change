<?php
namespace Change\Http\Web\Events;

use Change\Http\Web\Result\HtmlHeaderElement;
use Change\Http\Web\Result\Page as PageResult;
use Change\Website\Documents\FunctionalPage;
use Change\Http\Web\Blocks\Manager as BlocksManager;

/**
 * @name \Change\Http\Web\Events\ComposeFunctionalPage
 */
class ComposeFunctionalPage extends ComposePage
{
	/**
	 * Use Required Event Params: page
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$functionalPage = $event->getParam('page');
		if ($functionalPage instanceof FunctionalPage)
		{
			$this->setEventManager($functionalPage->getEventManager());

			$pageEvent = $this->newPageEvent($event, $functionalPage);
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
		if (($result = $pageEvent->getPageResult()) !== null && (($functionalPage = $pageEvent->getPage()) instanceof FunctionalPage))
		{
			/* @var $functionalPage FunctionalPage */
			$headElement = new HtmlHeaderElement('title');
			$headElement->setContent('Page: ' . $functionalPage->getLabel());
			$result->addNamedHeadAsString('title', $headElement);
		}
	}
}