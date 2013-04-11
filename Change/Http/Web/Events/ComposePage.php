<?php
namespace Change\Http\Web\Events;

use Change\Http\Web\Layout\Item as LayoutItem;
use Change\Http\Web\Layout\Block as LayoutBlock;
use Change\Http\Web\Layout\Factory as LayoutFactory;
use Change\Http\Web\Layout\Container as LayoutContainer;
use Change\Http\Web\Blocks\Manager as BlocksManager;
use Change\Http\Web\Result\Page as PageResult;
use Change\Website\Documents\Page;
use Zend\EventManager\EventManager;
use Zend\Http\Response as HttpResponse;

/**
 * @package Change\Http\Web\Events
 * @name \Change\Http\Web\Events\ComposePage
 */
class ComposePage
{
	/**
	 * @var EventManager
	 */
	protected $eventManager;

	/**
	 * @param PageEvent $pageEvent
	 * @throws \RuntimeException
	 */
	public function onPrepare($pageEvent)
	{
		if ($pageEvent->getPageResult() === null)
		{
			$page = $pageEvent->getPage();

			/* @var $pageTemplate \Change\Theme\Documents\PageTemplate */
			$pageTemplate = $page->getPageTemplate();
			if (!$pageTemplate)
			{
				throw new \RuntimeException('Page ' . $page . ' has no template', 999999);
			}

			$result = new PageResult($page->getId());
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

			$result->setTemplateId($pageTemplate->getId());
			$result->setHtmlTemplate($pageTemplate->getHtml());

			$factory = new LayoutFactory();
			$templateLayout = $factory->fromArray($pageTemplate->getDecodedEditableContent());
			$result->setTemplateLayout($templateLayout);

			$rawContentLayout = $factory->fromArray($page->getDecodedEditableContent());

			$contentLayout = array();
			foreach ($templateLayout as $item)
			{
				if ($item instanceof LayoutContainer)
				{
					if (isset($rawContentLayout[$item->getId()]))
					{
						$container = $rawContentLayout[$item->getId()];
						$contentLayout[$item->getId()] = $container;
					}
				}
			}

			$result->setContentLayout($contentLayout);
			$pageEvent->setPageResult($result);
		}
	}

	/**
	 * @return \Zend\EventManager\EventManagerInterface
	 */
	protected function getEventManager()
	{
		return $this->eventManager;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function setEventManager($eventManager)
	{
		$this->eventManager = $eventManager;
		$this->eventManager->attach(Page::EVENT_PAGE_PREPARE, array($this, 'onPrepare'), 5);
		$this->eventManager->attach(Page::EVENT_PAGE_COMPOSE, array($this, 'onCompose'), 5);
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param \Change\Website\Documents\Page $page
	 * @return PageEvent
	 */
	protected function newPageEvent($event, $page)
	{
		$pageEvent = new PageEvent(Page::EVENT_PAGE_PREPARE, $page);
		$pageEvent->setAcl($event->getAcl());
		$pageEvent->setAuthentication($event->getAuthentication());
		$pageEvent->setRequest($event->getRequest());
		$pageEvent->setUrlManager($event->getUrlManager());
		return $pageEvent;
	}

	/**
	 * @param PageEvent $pageEvent
	 */
	protected function dispatchPrepare($pageEvent)
	{
		$pageEvent->setName(Page::EVENT_PAGE_PREPARE);
		$results = $this->getEventManager()->trigger($pageEvent, function ($result)
		{
			return ($result instanceof PageResult);
		});
		if ($results->stopped() && ($results->last() instanceof PageResult))
		{
			$pageEvent->setPageResult($results->last());
		}
	}

	/**
	 * @param PageEvent $pageEvent
	 */
	protected function dispatchCompose($pageEvent)
	{
		$pageEvent->setName(Page::EVENT_PAGE_COMPOSE);
		$this->getEventManager()->trigger($pageEvent);
	}

	/**
	 * @param LayoutBlock[] $blocks
	 * @param BlocksManager $blockManager
	 * @return array
	 */
	protected function generateBlockResults($blocks, $blockManager)
	{
		$blockInputs = array();
		foreach ($blocks as $block)
		{
			/* @var $block LayoutBlock */
			$blockParameter = $blockManager->getParameters($block);
			$blockInputs[] = array($block, $blockParameter);
		}

		$blockResults = array();
		foreach ($blockInputs as $infos)
		{
			list($blockLayout, $parameters) = $infos;

			/* @var $blockLayout LayoutBlock */
			$result = $blockManager->getResult($blockLayout, $parameters);
			if (isset($result))
			{
				$blockResults[$blockLayout->getId()] = $result;
			}
		}
		return $blockResults;
	}

	/**
	 * @param PageResult $pageResult
	 * @return LayoutBlock[]
	 */
	protected function extractBlocks($pageResult)
	{
		$blocks = array();

		/* @var $layout LayoutItem[] */
		$layout = array_merge($pageResult->getTemplateLayout(), $pageResult->getContentLayout());

		foreach ($layout as $item)
		{
			if ($item instanceof LayoutBlock)
			{
				$blocks[] = $item;
			}
			else
			{
				$blocks = array_merge($blocks, $item->getBlocks());
			}
		}
		return $blocks;
	}

	/**
	 * @param PageEvent $pageEvent
	 */
	public function onCompose($pageEvent)
	{
		$result = $pageEvent->getPageResult();
		if ($result instanceof PageResult)
		{
			$head = array();
			$blockResults = $result->getBlockResults();
			$contentLayout = $result->getContentLayout();

			foreach ($result->getTemplateLayout() as $item)
			{
				$id = $item->getId();
				if ($item instanceof LayoutBlock)
				{
					$result->addHtmlFragment($id, $this->getHtmlForItem($item, $blockResults, $head));
				}
				elseif ($item instanceof LayoutContainer)
				{
					if (isset($contentLayout[$id]))
					{
						$result->addHtmlFragment($id, $this->getHtmlForItem($contentLayout[$id], $blockResults, $head));
					}
					else
					{
						$result->addHtmlFragment($id, '');
					}
				}
			}

			foreach ($head as $key => $value)
			{
				if (is_int($key))
				{
					$result->addHeadAsString($value);
				}
				else
				{
					$result->addNamedHeadAsString($key, $value);
				}
			}
		}
	}

	/**
	 * @param LayoutItem $item
	 * @param \Change\Http\Web\Blocks\Result[] $blockResults
	 * @param array $head
	 * @return string
	 */
	protected function getHtmlForItem($item, $blockResults, &$head)
	{
		if ($item instanceof LayoutBlock)
		{
			$innerHTML = '';
			$br = isset($blockResults[$item->getId()]) ? $blockResults[$item->getId()] : null;
			if ($br)
			{
				$innerHTML = $br->getHtml();
				foreach ($br->getHead() as $key => $value)
				{
					if (is_int($key))
					{
						$head[] = $value;
					}
					else
					{
						$head[$key] = $value;
					}
				}
			}
			if ($innerHTML)
			{
				return
					'<div data-type="block" data-id="' . $item->getId() . '" data-name="' . $item->getName() . '">' . $innerHTML
					. '</div>';
			}
			return '<div data-type="block" class="empty" data-id="' . $item->getId() . '" data-name="' . $item->getName()
				. '"></div>';
		}

		$innerHTML = '';
		foreach ($item->getItems() as $childItem)
		{
			$innerHTML .= $this->getHtmlForItem($childItem, $blockResults, $head);
		}
		if ($item instanceof \Change\Http\Web\Layout\Cell)
		{
			return '<div data-id="' . $item->getId() . '" class="span' . $item->getSize() . '">' . $innerHTML . '</div>';
		}
		elseif ($item instanceof \Change\Http\Web\Layout\Row)
		{
			return '<div class="row-fluid" data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML
				. '</div>';
		}
		elseif ($item instanceof LayoutContainer)
		{
			return
				'<div class="container-fluid" data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML
				. '</div>';
		}
	}
}