<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Website\Documents\FunctionalPage
 */
class FunctionalPage extends \Compilation\Rbs\Website\Documents\FunctionalPage
{
	/**
	 * @var \Rbs\Website\Documents\Section
	 */
	protected $section;

	/**
	 * @return \Change\Presentation\Interfaces\Section
	 */
	public function getSection()
	{
		return $this->section;
	}

	/**
	 * @param \Change\Presentation\Interfaces\Section $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATED, array($this, 'onCreated'), 10);
		$eventManager->attach(Event::EVENT_UPDATED, array($this, 'onUpdated'), 10);
	}

	/**
	 * @param Event $event
	 */
	public function onCreated(Event $event)
	{
		$page = $event->getDocument();
		if ($page instanceof FunctionalPage)
		{
			$codes = $this->getAllowedFunctionsCode();
			if (is_array($codes) && count($codes))
			{
				$page->checkDefaultSectionPageFunction();
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onUpdated(Event $event)
	{
		$page = $event->getDocument();
		if ($page instanceof FunctionalPage && (in_array('allowedFunctionsCode', $event->getParam('modifiedPropertyNames', array()))))
		{
			$codes = $this->getAllowedFunctionsCode();
			if (is_array($codes) && count($codes))
			{
				$relativePath = $pathRule->normalizePath($document->getTitle() . '.' . $document->getId() . '.html');
				$page->checkDefaultSectionPageFunction();
			}
		}
	}

	/**
	 * For each function this page can handle, check if:
	 * - it is not handled on the website
	 * - this page don't handle in any section
	 * Make this page handle each function on the website that matches these two conditions.
	 */
	protected function checkDefaultSectionPageFunction()
	{
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_SectionPageFunction');
		$query->andPredicates($query->eq('page', $this));
		$qb = $query->dbQueryBuilder();
		$qb->addColumn($qb->getFragmentBuilder()->alias($query->getColumn('functionCode'), 'fc'));
		$sq = $qb->query();
		$codes = array_diff($this->getAllowedFunctionsCode(), $sq->getResults($sq->getRowsConverter()->addStrCol('fc')));

		$website = $this->getWebsite();
		$query = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_SectionPageFunction');
		$query->andPredicates($query->eq('section', $website));
		$qb = $query->dbQueryBuilder();
		$qb->addColumn($qb->getFragmentBuilder()->alias($query->getColumn('functionCode'), 'fc'));
		$sq = $qb->query();
		$codes = array_diff($codes, $sq->getResults($sq->getRowsConverter()->addStrCol('fc')));

		foreach ($codes as $code)
		{
			/* @var $doc \Rbs\Website\Documents\SectionPageFunction */
			$doc = $this->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Website_SectionPageFunction');
			$doc->setSection($website);
			$doc->setPage($this);
			$doc->setFunctionCode($code);
			$doc->create();
		}
	}
}