<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Website\Documents\Section
 */
abstract class Section extends \Compilation\Rbs\Website\Documents\Section implements \Change\Presentation\Interfaces\Section
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
	 * @return \Rbs\Website\Documents\Section[]
	 */
	public function getSectionThread()
	{
		return $this->getSectionPath();
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getSectionPath()
	{
		$tm = $this->getDocumentServices()->getTreeManager();
		$sections = array();
		$tn = $tm->getNodeByDocument($this);
		if ($tn)
		{
			foreach($tm->getAncestorNodes($tn) as $node)
			{
				$doc = $node->setTreeManager($tm)->getDocument();
				if ($doc instanceof \Rbs\Website\Documents\Section)
				{
					$sections[] = $doc;
				}
			}
		}
		$sections[] = $this;
		return $sections;
	}
}