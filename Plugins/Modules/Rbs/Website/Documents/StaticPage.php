<?php
namespace Rbs\Website\Documents;

use Change\Http\Web\Result\HtmlHeaderElement;

/**
 * @name \Rbs\Website\Documents\StaticPage
 */
class StaticPage extends \Compilation\Rbs\Website\Documents\StaticPage
{
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