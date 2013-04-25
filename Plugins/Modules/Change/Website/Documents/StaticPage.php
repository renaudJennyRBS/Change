<?php
namespace Change\Website\Documents;

use Change\Http\Web\Result\HtmlHeaderElement;

/**
 * @name \Change\Website\Documents\StaticPage
 */
class StaticPage extends \Compilation\Change\Website\Documents\StaticPage
{
	/**
	 * @see \Change\Website\Documents\Page::onPrepare()
	 * @param \Change\Http\Web\Events\PageEvent $pageEvent
	 * @return \Change\Http\Web\Result\Page|null
	 */
	public function onPrepare($pageEvent)
	{
		$result = parent::onPrepare($pageEvent);
		if ($result)
		{
			$headElement = new HtmlHeaderElement('title');
			$headElement->setContent('Page: ' . $this->getNavigationTitle());
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
}