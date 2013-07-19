<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Compilation\Rbs\Website\Documents\FunctionalPage;

/**
 * @name \Rbs\Website\Documents\Section
 */
abstract class Section extends \Compilation\Rbs\Website\Documents\Section implements \Change\Presentation\Interfaces\Section
{
	/**
	 * @var \Rbs\Website\Documents\Page|boolean
	 */
	protected $indexPage = false;

	/**
	 * @param \Rbs\Website\Documents\Page $indexPage
	 * @return $this
	 */
	public function setIndexPage($indexPage)
	{
		$this->indexPage = $indexPage;
		return $this;
	}

	/**
	 * @return \Rbs\Website\Documents\Page|null
	 */
	public function getIndexPage()
	{
		if (false === $this->indexPage)
		{
			$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_Page');
			$subQuery = $query->getModelBuilder('Rbs_Website_SectionPageFunction', 'page');
			$subQuery->andPredicates($subQuery->eq('section', $this), $subQuery->eq('functionCode', 'Rbs_Website_Section'));
			$this->indexPage = $query->getFirstDocument();
		}
		return $this->indexPage;
	}

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
		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 10);
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDocumentDisplayPage(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof Section)
		{
			$page = $document->getIndexPage();
			if ($page instanceof FunctionalPage)
			{
				$page->setSection($document);
			}
			$event->setParam('page', $page);
			$event->stopPropagation();
		}
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