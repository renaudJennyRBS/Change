<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Event;

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
		$result = $event->getResult();
		$id = $result->getProperties()['id'];
		$condition = $result->getProperties()['condition'];
		$path = 'catalog/category/' . $id . '/' . $condition . '/products';
		$link = new \Change\Http\Rest\Result\Link($event->getUrlManager(), $path, 'products');
		$result->addAction($link);
	}

	/**
	 * @param Event $event
	 */
	public function onProductResult($event)
	{
		/* @var $result \Change\Http\Rest\Result\DocumentResult */
		$result = $event->getResult();
		$id = $result->getProperties()['id'];
		$condition = $result->getProperties()['condition'];
		$path = 'catalog/product/' . $id . '/' . $condition . '/categories';
		$link = new \Change\Http\Rest\Result\Link($event->getUrlManager(), $path, 'categories');
		$result->addAction($link);
	}
}