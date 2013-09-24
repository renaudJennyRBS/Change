<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\Listing
 */
class Listing extends \Compilation\Rbs\Catalog\Documents\Listing
{
	/**
	 * @param DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		$selfLinks = $documentResult->getRelLink('self');
		$selfLink = array_shift($selfLinks);
		if ($selfLink instanceof Link)
		{
			$pathParts = explode('/', $selfLink->getPathInfo());
			$link = new Link($documentResult->getUrlManager(), implode('/', $pathParts) . '/ProductCategorization/', 'productcategorizations');
			$documentResult->addLink($link);
		}
	}
}