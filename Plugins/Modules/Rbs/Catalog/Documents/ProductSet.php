<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\ProductSet
 */
class ProductSet extends \Compilation\Rbs\Catalog\Documents\ProductSet
{

	/**
	 * @param \Change\Http\Rest\Result\DocumentResult $documentResult
	 * @param \Change\Http\UrlManager $urlManager
	 * @param string $baseUrl
	 */
	protected function addLinkOnResult($documentResult, $urlManager, $baseUrl)
	{
		$documentResult->addLink(new Link($urlManager, $baseUrl . '/ProductListItems/', 'productListItems'));
	}

}
