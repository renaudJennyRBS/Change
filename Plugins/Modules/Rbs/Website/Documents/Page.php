<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Change\Http\Web\Events\PageEvent;
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
		return $this->getId() . ',' . $this->getLCID();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Page::getContentLayout()
	 * @return Layout
	 */
	public function getContentLayout()
	{
		return new Layout($this->getDecodedEditableContent());
	}

	/**
	 * @param Event $event
	 * @return \Change\Presentation\Interfaces\Page|null
	 */
	public function onDocumentDisplayPage($event)
	{
		if ($event instanceof Event)
		{
			$document = $event->getDocument();
			if ($document instanceof \Change\Presentation\Interfaces\Page)
			{
				return $document;
			}
		}
		return null;
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
				$callableTwigBlock = function(\Change\Presentation\Layout\Block $item) use ($twitterBootstrapHtml)
				{
					return '{{ pageResult.htmlBlock(\'' . $item->getId() . '\', ' . var_export($twitterBootstrapHtml->getBlockClass($item), true). ')|raw }}';
				};
				$twigLayout = $twitterBootstrapHtml->getHtmlParts($templateLayout, $pageLayout, $callableTwigBlock);

				$pageTemplate = $page->getPageTemplate();
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
		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 5);
		$this->eventManager->attach(\Change\Presentation\Interfaces\Page::EVENT_PAGE_PREPARE, array($this, 'onPrepare'), 5);
		$this->eventManager->attach(\Change\Presentation\Interfaces\Page::EVENT_PAGE_COMPOSE, array($this, 'onCompose'), 5);
	}
}