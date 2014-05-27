<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$themeManager = $applicationServices->getThemeManager();
		$themeManager->setCurrent($pageTemplate->getTheme());
		$section = $page->getSection();
		$websiteId = ($section && $section->getWebsite()) ? $section->getWebsite()->getId() : null;
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
		$workspace = $application->getWorkspace();
		$logging = $application->getLogging();
		$developmentMode = $application->inDevelopmentMode();

		$this->addResourceParts($result, $blocks, $themeManager, $logging, $developmentMode);

		$cachePath = $workspace->cachePath('twig', 'page', $result->getIdentifier() . '.twig');
		if ($developmentMode)
		{
			$cacheTime = (new \DateTime())->getTimestamp();
		}
		else
		{
			$cacheTime = max($page->getModificationDate()->getTimestamp(), $pageTemplate->getModificationDate()->getTimestamp());
		}

		if (!file_exists($cachePath) || filemtime($cachePath) <> $cacheTime)
		{
			$twitterBootstrapHtml = new \Change\Presentation\Layout\TwitterBootstrapHtml();
			$callableTwigBlock = function (\Change\Presentation\Layout\Block $item) use ($twitterBootstrapHtml)
			{
				return '{{ pageResult.htmlBlock(\'' . $item->getId() . '\', '
				. var_export($twitterBootstrapHtml->getBlockClass($item), true) . ')|raw }}';
			};
			$twigLayout = $twitterBootstrapHtml->getHtmlParts($templateLayout, $pageLayout, $callableTwigBlock);
			$twigLayout = array_merge($twigLayout, $twitterBootstrapHtml->getResourceParts());

			$htmlTemplate = str_replace(array_keys($twigLayout), array_values($twigLayout), $pageTemplate->getHtml());

			\Change\Stdlib\File::write($cachePath, $htmlTemplate);
			touch($cachePath, $cacheTime);
		}

		$templateManager = $event->getApplicationServices()->getTemplateManager();
		$result->setHtml($templateManager->renderTemplateFile($cachePath, array('pageResult' => $result)));
	}

	/**
	 * @param \Change\Http\Web\Result\Page $result
	 * @param \Change\Presentation\Layout\Block[] $blocks
	 * @param \Change\Presentation\Themes\ThemeManager $themeManager
	 * @param \Change\Logging\Logging $logging
	 * @param boolean $developmentMode
	 */
	public function addResourceParts(\Change\Http\Web\Result\Page $result, array $blocks,
		\Change\Presentation\Themes\ThemeManager $themeManager,
		\Change\Logging\Logging $logging = null, $developmentMode = false)
	{
		$blockNames = array();
		foreach($blocks as $block)
		{
			$blockName = $block->getName();
			$blockNames[$blockName] = $blockName;
		}

		$configuration = $themeManager->getDefault()->getAssetConfiguration();
		if ($themeManager->getCurrent() !== $themeManager->getDefault())
		{
			$configuration = $themeManager->getCurrent()->getAssetConfiguration($configuration);
		}

		$asseticManager = $themeManager->getAsseticManager($configuration);

		if ($developmentMode)
		{
			(new \Assetic\AssetWriter($themeManager->getAssetRootPath()))->writeManagerAssets($asseticManager);
		}

		$cssNames = $themeManager->getCssAssetNames($configuration, $blockNames);
		foreach($cssNames as $cssName)
		{
			try
			{
				$a = $asseticManager->get($cssName);
				$result->addCssAsset($a->getTargetPath());
			}
			catch (\Exception $e)
			{
				if ($logging)
				{
					$logging->warn('asset resource name not found: ' . $cssName);
					$logging->exception($e);
				}
			}
		}

		$jsNames = $themeManager->getJsAssetNames($configuration, $blockNames);
		foreach ($jsNames as $jsName)
		{
			try
			{
				$a = $asseticManager->get($jsName);
				$result->addJsAsset($a->getTargetPath());
			}
			catch (\Exception $e)
			{
				if ($logging)
				{
					$logging->warn('asset resource name not found: ' . $jsName);
					$logging->exception($e);
				}
			}
		}
	}
}