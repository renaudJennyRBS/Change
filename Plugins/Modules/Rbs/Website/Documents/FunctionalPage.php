<?php
namespace Rbs\Website\Documents;

use Change\Documents\Events\Event;
use Change\Documents\Query\Query;

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
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$query = new Query($this->getDocumentServices(), 'Rbs_Website_Section');
		$subQuery = $query->getModelBuilder('Rbs_Website_SectionPageFunction', 'section');
		$subQuery->andPredicates($subQuery->eq('page', $this));
		return $query->getDocuments()->toArray();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $publicationSections
	 * @return $this
	 */
	public function setPublicationSections($publicationSections)
	{
		return $this;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 10);
		$eventManager->attach('populatePathRule', array($this, 'onPopulatePathRule'), 10);
		$eventManager->attach('selectPathRule', array($this, 'onSelectPathRule'), 10);
	}

	/**
	 * @param Event $event
	 */
	public function onDocumentDisplayPage(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof FunctionalPage)
		{
			/* @var $pathRule \Change\Http\Web\PathRule */
			$pathRule = $event->getParam('pathRule');
			if ($pathRule && $pathRule->getSectionId())
			{
				/* @var $section Section */
				$section = $document->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
				$document->setSection($section);
				$event->setParam('page', $document);
				$event->stopPropagation();
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onPopulatePathRule(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof FunctionalPage)
		{
			/* @var $pathRule \Change\Http\Web\PathRule */
			$pathRule = $event->getParam('pathRule');
			$queryParameters = $event->getParam('queryParameters');
			$sectionPageFunction = $queryParameters['sectionPageFunction'];
			if ($sectionPageFunction)
			{
				$relativePath = $pathRule->normalizePath($document->getTitle() . '.' . $document->getId() . '.html');
				$section = $document->getDocumentManager()->getDocumentInstance($pathRule->getSectionId());
				if ($section instanceof Topic && $section->getPathPart())
				{
					$relativePath = $section->getPathPart() . '/' . $relativePath;
				}
				$pathRule->setRelativePath($relativePath);
				$pathRule->setQueryParameters(array('sectionPageFunction' => $sectionPageFunction));
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onSelectPathRule(Event $event)
	{
		$document = $event->getDocument();
		if ($document instanceof FunctionalPage)
		{
			/* @var $pathRule \Change\Http\Web\PathRule */
			$pathRules = $event->getParam('pathRules');
			$queryParameters = $event->getParam('queryParameters');
			unset($queryParameters['sectionPageFunction']);
			$event->setParam('pathRule', $pathRules[0]);
		}
	}
}