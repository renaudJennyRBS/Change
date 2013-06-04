<?php
namespace Rbs\Website\Documents;

/**
 * @name \Rbs\Website\Documents\FunctionalPage
 */
class FunctionalPage extends \Compilation\Rbs\Website\Documents\FunctionalPage
{
	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$dqb = new \Change\Documents\Query\Builder($this->getDocumentServices(), 'Rbs_Website_Section');
		$dqb->getModelBuilder('Rbs_Website_SectionPageFunction', 'section')->eq('page', $this);
		return $dqb->getDocuments();
	}
}