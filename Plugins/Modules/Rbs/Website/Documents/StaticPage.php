<?php
namespace Rbs\Website\Documents;

use Change\Http\Web\Result\HtmlHeaderElement;

/**
 * @name \Rbs\Website\Documents\StaticPage
 */
class StaticPage extends \Compilation\Rbs\Website\Documents\StaticPage
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$callback = function(\Change\Documents\Events\Event $event)
		{
			/* @var $page StaticPage */
			$page = $event->getDocument();
			if ($page->getSection())
			{
				$tm = $page->getDocumentServices()->getTreeManager();
				$parentNode = $tm->getNodeByDocument($page->getSection());
				if ($parentNode)
				{
					$tm->insertNode($parentNode, $this);
				}
			}
		};
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_CREATED, $callback);

		$callback = function(\Change\Documents\Events\Event $event)
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
						$tm->insertNode($parentNode, $this);
					}
				}
			}
		};
		$eventManager->attach(\Change\Documents\Events\Event::EVENT_UPDATED, $callback);
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
	 * @param \Change\Documents\Events\Event $event
	 * @return \Change\Presentation\Interfaces\Page|null
	 */
	public function onDocumentDisplayPage($event)
	{
		$doc = parent::onDocumentDisplayPage($event);
		if ($doc)
		{
			$tn = $this->getDocumentServices()->getTreeManager()->getNodeByDocument($this);
			$event->getParam('pathRule')->setSectionId($tn->getParentId());
		}
		return $doc;
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