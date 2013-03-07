<?php
namespace ChangeTests\Change\Documents\TestAssets;

/**
 * @name \ChangeTests\Change\Documents\TestAssets\MemoryInstance
 */
class MemoryInstance
{
	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return \Project\Tests\Documents\Basic
	 */
	public function getInstanceRo5001(\Change\Documents\DocumentServices $documentServices)
	{
		/* @var $doc \Project\Tests\Documents\Basic */
		$doc = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$doc->setPStr('lab 5001');
		$doc->initialize(5001, \Change\Documents\DocumentManager::STATE_LOADED);
		return $doc;
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @return \Project\Tests\Documents\Localized
	 */
	public function getInstanceRo5002(\Change\Documents\DocumentServices $documentServices)
	{
		/* @var $doc \Project\Tests\Documents\Localized */
		$doc = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc->setPStr('lab 5002');
		$doc->setPLStr('Localized 5002');
		$doc->initialize(5002, \Change\Documents\DocumentManager::STATE_LOADED);
		return $doc;
	}
}