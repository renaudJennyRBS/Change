<?php
namespace Change\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Change\Website\Documents\Section
 */
class Section extends \Compilation\Change\Website\Documents\Section implements \Change\Presentation\Interfaces\Section
{
	/**
	 * @return string
	 */
	public function getPathSuffix()
	{
		return '/';
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 5);
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
			if ($document instanceof Section)
			{
				$page = $document->getIndexPage();
				if ($page instanceof \Change\Presentation\Interfaces\Page)
				{
					return $page;
				}
			}
		}
		return null;
	}

	/**
	 * @return \Change\Website\Documents\Section[]
	 */
	public function getSectionThread()
	{
		$tm = $this->getDocumentServices()->getTreeManager();
		$tn = $tm->getNodeByDocument($this);
		$sections = array();
		foreach($tm->getAncestorNodes($tn) as $node)
		{
			$doc = $node->getDocument();
			if ($doc instanceof \Change\Website\Documents\Section)
			{
				$sections[] = $doc;
			}
		}
		$sections[] = $this;
		return $sections;
	}

	/**
	 * @throws \LogicException
	 * @return \Change\Presentation\Interfaces\Website
	 */
	public function getWebsite()
	{
		throw new \LogicException('A section must implement getWebsite()', 999999);
	}

	/**
	 * @throws \LogicException
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		throw new \LogicException('A publishable document must implement getPublicationSections()', 999999);
	}
}