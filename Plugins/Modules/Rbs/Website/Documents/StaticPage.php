<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Change\Http\Web\Result\HtmlHeaderElement;

/**
 * @name \Rbs\Website\Documents\StaticPage
 */
class StaticPage extends \Compilation\Rbs\Website\Documents\StaticPage
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 10);

		$callback = function (Event $event)
		{
			/* @var $page StaticPage */
			$page = $event->getDocument();
			if ($page->getSection())
			{
				$tm = $page->getDocumentServices()->getTreeManager();
				$parentNode = $tm->getNodeByDocument($page->getSection());
				if ($parentNode)
				{
					$tm->insertNode($parentNode, $page);
				}
			}
		};
		$eventManager->attach(Event::EVENT_CREATED, $callback);

		$callback = function (Event $event)
		{
			/* @var $page StaticPage */
			if (in_array('section', $event->getParam('modifiedPropertyNames', array())))
			{
				$page = $event->getDocument();
				$tm = $page->getDocumentServices()->getTreeManager();
				$tm->deleteDocumentNode($page);
				if ($page->getSection())
				{
					$parentNode = $tm->getNodeByDocument($page->getSection());
					if ($parentNode)
					{
						$tm->insertNode($parentNode, $page);
					}
				}
			}
		};
		$eventManager->attach(Event::EVENT_UPDATED, $callback);

		$eventManager->attach('populatePathRule', array($this, 'onPopulatePathRule'), 5);
	}

	/**
	 * @param Event $event
	 */
	public function onPopulatePathRule(Event $event)
	{
		/* @var $pathRule \Change\Http\Web\PathRule */
		$pathRule = $event->getParam('pathRule');

		$relativePath = $this->getTitle() . '.' . $this->getId() . '.html';
		$section = $this->getSection();
		if ($section !== null && $section->getPathPart())
		{
			$relativePath = $section->getPathPart() . '/' . $relativePath;
		}
		$pathRule->setRelativePath($relativePath);
	}

	/**
	 * @param Event $event
	 */
	public function onDocumentDisplayPage(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof \Change\Presentation\Interfaces\Page)
		{
			$event->setParam('page', $document);
			$event->stopPropagation();
		}
	}

	/**
	 * @see \Rbs\Website\Documents\Page::onPrepare()
	 * @param \Change\Http\Web\Events\PageEvent $pageEvent
	 * @return \Change\Http\Web\Result\Page|null
	 */
	public function onPrepare($pageEvent)
	{
		$result = parent::onPrepare($pageEvent);
		if ($result)
		{
			$headElement = new HtmlHeaderElement('title');
			$headElement->setContent('Page: ' . $this->getTitle());
			$result->addNamedHeadAsString('title', $headElement);
		}
		return $result;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$section = $this->getSection();
		return $section ? array($section) : array();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $publicationSections
	 */
	public function setPublicationSections($publicationSections)
	{
		// TODO: Implement setPublicationSections() method.
	}
}