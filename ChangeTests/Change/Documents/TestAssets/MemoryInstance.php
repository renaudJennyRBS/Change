<?php
namespace ChangeTests\Change\Documents\TestAssets;

/**
 * @name \ChangeTests\Change\Documents\TestAssets\MemoryInstance
 */
class MemoryInstance
{
	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Project\Tests\Documents\Basic
	 */
	public function getInstanceRo5001(\Change\Documents\DocumentManager $documentManager)
	{
		/* @var $doc \Project\Tests\Documents\Basic */
		$doc = $documentManager->getNewDocumentInstanceByModelName('Project_Tests_Basic');
		$doc->setPStr('lab 5001');
		$doc->initialize(5001, \Change\Documents\AbstractDocument::STATE_LOADED);
		return $doc;
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Project\Tests\Documents\Localized
	 */
	public function getInstanceRo5002(\Change\Documents\DocumentManager $documentManager)
	{
		/* @var $doc \Project\Tests\Documents\Localized */
		$doc = $documentManager->getNewDocumentInstanceByModelName('Project_Tests_Localized');
		$doc->setPStr('lab 5002');
		$doc->getCurrentLocalization()->setPLStr('Localized 5002');
		$doc->initialize(5002, \Change\Documents\AbstractDocument::STATE_LOADED);
		return $doc;
	}
}