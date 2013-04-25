<?php
namespace Change\Presentation\Themes;

use Change\Presentation\Interfaces\Page;
use Change\Presentation\Interfaces\PageTemplate;
use Change\Presentation\Layout\Layout;
use Change\Http\Web\Events\PageEvent;
use Zend\EventManager\EventManager;
use Change\Http\Web\Result\Page as PageResult;
use Zend\Http\Response as HttpResponse;

/**
 * Class DefaultPage
 * @package Change\Presentation\Themes
 * @name \Change\Presentation\Themes\DefaultPage
 */
class DefaultPage implements Page
{
	/**
	 * @var ThemeManager
	 */
	protected $themeManager;

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var \Zend\EventManager\EventManagerInterface
	 */
	protected $eventManager;

	/**
	 * @var \Change\Presentation\Interfaces\ThemeResource
	 */
	protected $layoutResource;

	/**
	 * @param ThemeManager $themeManager
	 * @param string $identifier
	 */
	function __construct(ThemeManager $themeManager, $identifier = 'default')
	{
		$this->themeManager = $themeManager;
		$this->identifier = $identifier;
	}

	/**
	 * Retrieve the event manager
	 * @api
	 * @return \Zend\EventManager\EventManagerInterface
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = new EventManager('default.theme');
			$this->eventManager->setSharedManager($this->themeManager->getPresentationServices()->getApplicationServices()
				->getApplication()->getSharedEventManager());
			$this->eventManager->attach(Page::EVENT_PAGE_PREPARE, array($this, 'onPrepare'), 5);
			$this->eventManager->attach(Page::EVENT_PAGE_COMPOSE, array($this, 'onCompose'), 5);
		}
		return $this->eventManager;
	}

	/**
	 * @api
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->identifier;
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Presentation\Interfaces\ThemeResource
	 */
	protected function getLayoutResource()
	{
		if ($this->layoutResource === null)
		{
			$this->layoutResource = $this->themeManager->getDefault()->getResource('Layout/Page/' . $this->getIdentifier() . '.json');
			if (!$this->layoutResource->isValid())
			{
				throw new \RuntimeException($this->getIdentifier() . '.json resource not found', 999999);
			}
		}
		return $this->layoutResource;
	}

	/**
	 * @return \Datetime
	 */
	public function getModificationDate()
	{
		return $this->getLayoutResource()->getModificationDate();
	}


	/**
	 * @api
	 * @return PageTemplate
	 */
	public function getPageTemplate()
	{
		return $this->themeManager->getDefault()->getPageTemplate('default');
	}

	/**
	 * @return Layout
	 */
	public function getContentLayout()
	{
		$config = $this->getLayoutResource()->getContent();
		return new Layout(json_decode($config, true));
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
		if (!$pageEvent instanceof PageEvent || $pageEvent->getPage() !== $this | $pageEvent->getPageResult() !== null)
		{
			return null;
		}

		/* @var $page DefaultPage */
		$page = $pageEvent->getPage();

		$pageTemplate = $page->getPageTemplate();
		$result = new PageResult($page->getIdentifier());
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$this->themeManager->setCurrent($pageTemplate->getTheme());

		$pageTemplate = $page->getPageTemplate();

		$templateLayout = $pageTemplate->getContentLayout();
		$result->setTemplateLayout($templateLayout);
		$pageLayout = $page->getContentLayout();
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
			/* @var $page DefaultPage */
			$page = $pageEvent->getPage();

			$application = $pageEvent->getApplicationServices()->getApplication();
			$cachePath = $application->getWorkspace()->cachePath('twig', 'page', $result->getIdentifier() . '.twig');
			$cacheTime = $page->getModificationDate()->getTimestamp();

			if (!file_exists($cachePath) || filemtime($cachePath) <> $cacheTime)
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
				touch($cachePath, $cacheTime);
			}

			$templateManager = $pageEvent->getPresentationServices()->getTemplateManager();
			$renderer = function () use ($result, $cachePath, $templateManager)
			{
				return $templateManager->renderTemplateFile($cachePath, array('pageResult' => $result));
			};
			$result->setRenderer($renderer);
		}
	}
}