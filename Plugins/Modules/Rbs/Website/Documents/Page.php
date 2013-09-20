<?php
namespace Rbs\Website\Documents;

use Change\Http\Web\Events\PageEvent;
use Change\Http\Web\Result\HtmlHeaderElement;
use Change\Http\Web\Result\Page as PageResult;
use Change\Presentation\Layout\Layout;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Website\Documents\Page
 */
abstract class Page extends \Compilation\Rbs\Website\Documents\Page implements \Change\Presentation\Interfaces\Page
{
	/**
	 * @see \Change\Presentation\Interfaces\Page::getIdentifier()
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->getId() . ',' . $this->getCurrentLCID();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getContentLayout()
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getCurrentLocalization()->getEditableContent());
	}

	/**
	 * Required Event params : getPage
	 * Return A result initialized with : TemplateLayout, ContentLayout
	 * @param PageEvent $pageEvent
	 * @return \Change\Http\Web\Result\Page|null
	 * @throws \RuntimeException
	 */
	public function onPrepare($pageEvent)
	{
		//TODO check $this = $page
		if (!$pageEvent instanceof PageEvent || $pageEvent->getPage() !== $this | $pageEvent->getPageResult() !== null)
		{
			return null;
		}

		$page = $pageEvent->getPage();

		$pageTemplate = $page->getPageTemplate();
		if (!$pageTemplate)
		{
			throw new \RuntimeException('Page ' . $page . ' has no template', 999999);
		}

		$result = new PageResult($page->getIdentifier());
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

		$headElement = new HtmlHeaderElement('title');
		$headElement->setContent($this->getTitle());
		$result->addNamedHeadAsString('title', $headElement);

		$pageEvent->getPresentationServices()->getThemeManager()->setCurrent($pageTemplate->getTheme());
		$pageTemplate = $page->getPageTemplate();
		$templateLayout = $pageTemplate->getContentLayout();
		$result->setTemplateLayout($templateLayout);
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
		$result->setContentLayout($pageLayout);
		return $result;
	}

	/**
	 * Required Event params : getPage, getTemplateLayout, getContentLayout
	 * Add Renderer to result
	 * @param PageEvent $pageEvent
	 */
	public function onCompose($pageEvent)
	{
		$result = $pageEvent->getPageResult();
		if ($result instanceof PageResult)
		{
			$page = $pageEvent->getPage();

			$application = $pageEvent->getApplicationServices()->getApplication();
			$cachePath = $application->getWorkspace()->cachePath('twig', 'page', $result->getIdentifier() . '.twig');
			if (!file_exists($cachePath) || filemtime($cachePath) < $page->getModificationDate()->getTimestamp())
			{
				$templateLayout = $result->getTemplateLayout();
				$pageLayout = $result->getContentLayout();

				$twitterBootstrapHtml = new \Change\Presentation\Layout\TwitterBootstrapHtml();
				$callableTwigBlock = function (\Change\Presentation\Layout\Block $item) use ($twitterBootstrapHtml)
				{
					return '{{ pageResult.htmlBlock(\'' . $item->getId() . '\', '
					. var_export($twitterBootstrapHtml->getBlockClass($item), true) . ')|raw }}';
				};
				$twigLayout = $twitterBootstrapHtml->getHtmlParts($templateLayout, $pageLayout, $callableTwigBlock);

				$pageTemplate = $page->getPageTemplate();
				$blockNames = array();
				foreach($templateLayout->getBlocks() as $block)
				{
					$blockNames[$block->getName()] = true;
				}
				foreach($pageLayout->getBlocks() as $block)
				{
					$blockNames[$block->getName()] = true;
				}
				$pageEvent->getPresentationServices()->getThemeManager()
					->configurePageTemplate($pageTemplate, array_keys($blockNames), $this->getApplicationServices()->getApplication()->getWorkspace());
				//TODO cssHead like Js
				//$twigLayout['<!-- cssHead -->'] = $pageTemplate->getCssAssetCollection()->dump();
				$twigLayout['<!-- jsFooter -->'] = $pageTemplate->getJsAssetCollection()->dump();

				$htmlTemplate = str_replace(array_keys($twigLayout), array_values($twigLayout), $pageTemplate->getHtml());

				\Change\Stdlib\File::write($cachePath, $htmlTemplate);
			}

			$templateManager = $pageEvent->getPresentationServices()->getTemplateManager();
			$renderer = function () use ($result, $cachePath, $templateManager)
			{
				return $templateManager->renderTemplateFile($cachePath, array('pageResult' => $result));
			};
			$result->setRenderer($renderer);
		}
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		$eventManager->attach(\Change\Presentation\Interfaces\Page::EVENT_PAGE_PREPARE, array($this, 'onPrepare'), 5);
		$eventManager->attach(\Change\Presentation\Interfaces\Page::EVENT_PAGE_COMPOSE, array($this, 'onCompose'), 5);
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getModificationDate()
	 * @return \DateTime
	 */
	public function getModificationDate()
	{
		return $this->getCurrentLocalization()->getModificationDate();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getModificationDate()
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getTitle();
	}
}