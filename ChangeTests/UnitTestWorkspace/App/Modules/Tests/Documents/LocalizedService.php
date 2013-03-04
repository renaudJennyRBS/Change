<?php
namespace Project\Tests\Documents;
/**
 * @name \Project\Tests\Documents\LocalizedService
 */
class LocalizedService extends \Compilation\Project\Tests\Documents\AbstractLocalizedService
{
	/**
	 * @return \Project\Tests\Documents\Localized
	 */
	public function getInstanceRo5002()
	{
		$doc = $this->getNewDocumentInstance();
		$doc->setPStr('lab 5002');
		$doc->setPLStr('Localized 5002');
		$doc->initialize(5002, \Change\Documents\DocumentManager::STATE_LOADED);
		return $doc;
	}
}