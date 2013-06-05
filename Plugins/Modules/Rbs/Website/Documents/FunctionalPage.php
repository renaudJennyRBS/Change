<?php
namespace Rbs\Website\Documents;

use Change\Documents\Query\Builder;

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
	 */
	public function setSection($section)
	{
		$this->section = $section;
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$query = new Builder($this->getDocumentServices(), 'Rbs_Website_Section');
		$subQuery = $query->getModelBuilder('Rbs_Website_SectionPageFunction', 'section');
		$subQuery->andPredicates($subQuery->eq('page', $this));
		return $query->getDocuments();
	}

	/**
	 * @param \Change\Documents\AbstractDocument $publicationSections
	 */
	public function setPublicationSections($publicationSections)
	{
		// TODO: Implement setPublicationSections() method.
	}
}