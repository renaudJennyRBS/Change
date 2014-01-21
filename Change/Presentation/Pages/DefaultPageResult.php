<?php
namespace Change\Presentation\Pages;

/**
* @name \Change\Presentation\Pages\DefaultPageResult
*/
class DefaultPageResult
{
	public function onGetPageResult(PageEvent $event)
	{
		if (!($event->getPage() instanceof \Change\Presentation\Interfaces\Page))
		{
			return;
		}

		/* @var $page \Change\Presentation\Interfaces\Page */
		$page = $event->getPage();
		$pageManager = $event->getPageManager();
		$pageTemplate = $page->getTemplate();
		$applicationServices = $event->getApplicationServices();

		$result = $event->getPageResult();
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$applicationServices->getThemeManager()->setCurrent($pageTemplate->getTheme());
		$section = $page->getSection();
		$websiteId =  ($section && $section->getWebsite()) ? $section->getWebsite()->getId() : null;
		$templateLayout = $pageTemplate->getContentLayout($websiteId);

		$pageLayout = $page->getContentLayout();
		$containers = array();
		foreach ($templateLayout->getItems() as $item)
		{
			if ($item instanceof \Change\Presentation\Layout\Container)
			{
				$container = $pageLayout->getById($item->getId());
				if ($container)
				{
					$containers[] = $container;
				}
			}
		}
		$pageLayout->setItems($containers);

		$blocks = array_merge($templateLayout->getBlocks(), $pageLayout->getBlocks());

		if (count($blocks))
		{
			$blockManager = $applicationServices->getBlockManager();

			$blockInputs = array();
			foreach ($blocks as $block)
			{
				/* @var $block \Change\Presentation\Layout\Block */
				$blockParameter = $blockManager->getParameters($block, $pageManager->getHttpWebEvent());
				$blockInputs[] = array($block, $blockParameter);
			}

			$blockResults = array();
			foreach ($blockInputs as $infos)
			{
				list($blockLayout, $parameters) = $infos;

				/* @var $blockLayout \Change\Presentation\Layout\Block */
				$blockResult = $blockManager->getResult($blockLayout, $parameters, $pageManager->getHttpWebEvent());
				if (isset($blockResult))
				{
					$blockResults[$blockLayout->getId()] = $blockResult;
				}
			}
			$result->setBlockResults($blockResults);
		}

		$application = $event->getApplication();
		$cachePath = $application->getWorkspace()->cachePath('twig', 'page', $result->getIdentifier() . '.twig');
		$cacheTime = max($page->getModificationDate()->getTimestamp(), $pageTemplate->getModificationDate()->getTimestamp());

		if (!file_exists($cachePath) || filemtime($cachePath) <> $cacheTime)
		{
			$themeManager = $applicationServices->getThemeManager();
			$twitterBootstrapHtml = new \Change\Presentation\Layout\TwitterBootstrapHtml();
			$callableTwigBlock = function(\Change\Presentation\Layout\Block $item) use ($twitterBootstrapHtml)
			{
				return '{{ pageResult.htmlBlock(\'' . $item->getId() . '\', ' . var_export($twitterBootstrapHtml->getBlockClass($item), true). ')|raw }}';
			};
			$twigLayout = $twitterBootstrapHtml->getHtmlParts($templateLayout, $pageLayout, $callableTwigBlock);
			$twigLayout = array_merge($twigLayout, $twitterBootstrapHtml->getResourceParts($templateLayout, $pageLayout, $themeManager, $applicationServices, $application->inDevelopmentMode()));

			$htmlTemplate = str_replace(array_keys($twigLayout), array_values($twigLayout), $pageTemplate->getHtml());

			\Change\Stdlib\File::write($cachePath, $htmlTemplate);
			touch($cachePath, $cacheTime);
		}

		$templateManager = $event->getApplicationServices()->getTemplateManager();
		$result->setHtml($templateManager->renderTemplateFile($cachePath, array('pageResult' => $result)));
	}
}