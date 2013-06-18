<?php
namespace Change\Http\Web\Events;

use Change\Http\Web\Result\Page as PageResult;
use Change\Presentation\Interfaces\Page;
use Change\Http\Web\Result\HtmlHeaderElement;
use Zend\Http\Response as HttpResponse;

/**
 * @package Change\Http\Web\Events
 * @name \Change\Http\Web\Events\ComposePage
 */
class ComposePage
{
	/**
	 * Use Required Event Params: page
	 * @param \Change\Http\Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$page = $event->getParam('page');
		if ($page instanceof Page)
		{
			$pageEvent = $this->newPageEvent($event, $page);
			$this->dispatchPrepare($page->getEventManager(), $pageEvent);
			$pageResult = $pageEvent->getPageResult();

			if ($pageResult instanceof PageResult)
			{
				$pageResult->getHeaders()->addHeaderLine('Content-Type: text/html;charset=utf-8');
				$base = $event->getUrlManager()->getByPathInfo(null)->normalize()->toString();
				$headElement = new HtmlHeaderElement('base', array('href' => $base, 'target' => '_self'));
				$pageResult->addNamedHeadAsString('base', $headElement);

				$this->dispatchCompose($page->getEventManager(), $pageEvent);

				$blocks = array_merge($pageResult->getTemplateLayout()->getBlocks(), $pageResult->getContentLayout()->getBlocks());

				if (count($blocks))
				{
					$blockManager = $event->getPresentationServices()->getBlockManager();
					$blockManager->setDocumentServices($event->getDocumentServices());

					$blockInputs = array();
					foreach ($blocks as $block)
					{
						/* @var $block \Change\Presentation\Layout\Block */
						$blockParameter = $blockManager->getParameters($block, $event);
						$blockInputs[] = array($block, $blockParameter);
					}

					$blockResults = array();
					foreach ($blockInputs as $infos)
					{
						list($blockLayout, $parameters) = $infos;

						/* @var $blockLayout \Change\Presentation\Layout\Block */
						$result = $blockManager->getResult($blockLayout, $parameters, $event->getUrlManager());
						if (isset($result))
						{
							$pageResult->addHeads($result->getHead());
							$blockResults[$blockLayout->getId()] = $result;
						}
					}
					$pageResult->setBlockResults($blockResults);
				}

				$event->setResult($pageResult);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param Page $page
	 * @return PageEvent
	 */
	protected function newPageEvent($event, $page)
	{
		$pageEvent = new PageEvent(Page::EVENT_PAGE_PREPARE, $page);
		$pageEvent->setAcl($event->getAcl());
		$pageEvent->setAuthentication($event->getAuthentication());
		$pageEvent->setRequest($event->getRequest());
		$pageEvent->setUrlManager($event->getUrlManager());
		$pageEvent->setApplicationServices($event->getApplicationServices());
		$pageEvent->setPresentationServices($event->getPresentationServices());
		return $pageEvent;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 * @param PageEvent $pageEvent
	 */
	protected function dispatchPrepare($eventManager, $pageEvent)
	{
		$pageEvent->setName(Page::EVENT_PAGE_PREPARE);
		$results = $eventManager->trigger($pageEvent, function ($result)
		{
			return ($result instanceof PageResult);
		});
		if ($results->stopped() && ($results->last() instanceof PageResult))
		{
			$pageEvent->setPageResult($results->last());
		}
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 * @param PageEvent $pageEvent
	 */
	protected function dispatchCompose($eventManager, $pageEvent)
	{
		$pageEvent->setName(Page::EVENT_PAGE_COMPOSE);
		$eventManager->trigger($pageEvent);
	}
}