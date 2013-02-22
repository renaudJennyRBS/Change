<?php
namespace Project\Tests\Documents;
/**
 * @name \Project\Tests\Documents\BasicService
 */
class BasicService extends \Compilation\Project\Tests\Documents\AbstractBasicService
{
	/**
	 * @return \Project\Tests\Documents\Basic
	 */
	public function getInstanceRo5001()
	{
		$doc = $this->getNewDocumentInstance();
		$doc->setPStr('lab 5001');
		$doc->initialize(5001, \Change\Documents\DocumentManager::STATE_LOADED);
		return $doc;
	}
}