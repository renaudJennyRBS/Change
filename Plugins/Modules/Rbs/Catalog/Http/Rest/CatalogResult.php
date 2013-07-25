<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Documents\Events\Event;
use Change\Http\Rest\Result\DocumentLink;

/**
 * @name \Rbs\Catalog\Http\Rest\CatalogResult
 */
class CatalogResult
{
	/**
	 * @param Event $event
	 */
	public function onCategoryResult($event)
	{
		/* @var $result \Change\Http\Rest\Result\DocumentResult */
		$result = $event->getParam('restResult');
		$document = $event->getDocument();
		$docLink = new DocumentLink($event->getParam('urlManager'), $document);
		$pathParts = explode('/', $docLink->getPathInfo());
		array_pop($pathParts);
		$link = new \Change\Http\Rest\Result\Link($event->getParam('urlManager'), implode('/', $pathParts) . '/ProductCategorization/', 'products');
		$result->addLink($link);
	}

	/**
	 * @param Event $event
	 */
	public function onProductResult($event)
	{
		/* @var $result \Change\Http\Rest\Result\DocumentResult */
		$result = $event->getParam('restResult');
		$document = $event->getDocument();
		$docLink = new DocumentLink($event->getParam('urlManager'), $document);
		$pathParts = explode('/', $docLink->getPathInfo());
		array_pop($pathParts);
		$link = new \Change\Http\Rest\Result\Link($event->getParam('urlManager'), implode('/', $pathParts) . '/ProductCategorization/', 'categories');
		$result->addLink($link);
	}
}