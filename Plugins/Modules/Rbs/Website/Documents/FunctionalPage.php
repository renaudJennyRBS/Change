<?php
namespace Change\Website\Documents;

/**
 * @name \Change\Website\Documents\FunctionalPage
 */
class FunctionalPage extends \Compilation\Change\Website\Documents\FunctionalPage
{
	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$dqb = new \Change\Documents\Query\Builder($this->getDocumentServices(), 'Change_Website_Section');
		$dqb->getModelBuilder('Change_Website_SectionPageFunction', 'section')->eq('page', $this);
		return $dqb->getDocuments();
	}
}