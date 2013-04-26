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

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$ds = $this->getDocumentServices();
		$node = $ds->getTreeManager()->getNodeByDocument($this);
		if (!$node)
		{
			return array();
		}
		$section = $this->getDocumentManager()->getDocumentInstance($node->getParentId(),
			$ds->getModelManager()->getModelByName('Change_Website_Section'));
		if ($section)
		{
			return array($section);
		}
		return array();
	}
}