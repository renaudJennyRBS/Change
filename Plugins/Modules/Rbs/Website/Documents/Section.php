<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Rbs\Website\Documents\FunctionalPage;

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
		$eventManager->attach('getPageByFunction', array($this, 'getPageByFunction'), 10);
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
	 * @param \Change\Documents\Events\Event $event
	 */
	public function getPageByFunction(Event $event)
	{
		$section = $event->getDocument();
		if ($section instanceof Section)
		{
			$functionCode = $event->getParam('functionCode');
			$treeNode = $this->getDocumentServices()->getTreeManager()->getNodeByDocument($section);
			if ($treeNode)
			{
				$sectionIds = $treeNode->getAncestorIds();
				$sectionIds[] = $section->getId();

				$q = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_SectionPageFunction');
				$q->andPredicates($q->eq('functionCode', $functionCode), $q->in('section', $sectionIds));
				$dbq = $q->dbQueryBuilder();
				$fb = $dbq->getFragmentBuilder();
				$dbq->addColumn($fb->getDocumentColumn('page'))->addColumn($fb->getDocumentColumn('section'));
				$sq = $dbq->query();

				$pageBySections = $sq->getResults($sq->getRowsConverter()->addIntCol('page', 'section')
					->indexBy('section')->singleColumn('page'));
				if (count($pageBySections))
				{
					foreach (array_reverse($sectionIds) as $sectionId)
					{
						if (isset($pageBySections[$sectionId]))
						{
							$page = $this->getDocumentManager()->getDocumentInstance($pageBySections[$sectionId]);
							$section = $this->getDocumentManager()->getDocumentInstance($sectionId);
							$event->setParam('page', $page);
							$event->setParam('section', $section);
							return;
						}
					}
				}
			}
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
			foreach ($tm->getAncestorNodes($tn) as $node)
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

	/**
	 * @see \Change\Presentation\Interfaces\Section::getTitle()
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getCurrentLocalization()->getTitle();
	}

	/**
	 * @see \Change\Presentation\Interfaces\Section::getPathPart()
	 * @return string
	 */
	public function getPathPart()
	{
		return $this->getCurrentLocalization()->getPathPart();
	}
}