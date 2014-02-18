<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\Link;

/**
 * @name \Rbs\Catalog\Documents\ProductList
 */
class ProductList extends \Compilation\Rbs\Catalog\Documents\ProductList
{
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$documentResult = $restResult;
			$selfLinks = $documentResult->getRelLink('self');
			$selfLink = array_shift($selfLinks);
			if ($selfLink instanceof Link)
			{
				$pathParts = explode('/', $selfLink->getPathInfo());
				$link = new Link($documentResult->getUrlManager(),
					implode('/', $pathParts) . '/ProductListItems/', 'productListItems');
				$documentResult->addLink($link);
			}
		}
	}
}