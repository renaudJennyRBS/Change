<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\Product
 */
class Product extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('productId', Property::TYPE_INTEGER, true);
		$parameters->addParameterMeta('webStoreId', Property::TYPE_INTEGER, true);
		$parameters->addParameterMeta('categoryId', Property::TYPE_INTEGER, false);
		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('productId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Catalog\Documents\Product)
			{
				$parameters->setParameterValue('productId', $document->getId());
			}
		}

		$request = $event->getHttpRequest();
		if ($parameters->getParameter('webStoreId') === null)
		{
			$webStoreId = $request->getQuery('webStoreId');
			if ($webStoreId) {$parameters->setParameterValue('webStoreId', $webStoreId);}
		}
		if ($parameters->getParameter('categoryId') === null)
		{
			$categoryId = $request->getQuery('categoryId');
			if ($categoryId) {$parameters->setParameterValue('categoryId', $categoryId);}
		}
		$password = $request->getPost('password');
		if ($password) {$parameters->setParameterValue('password', $password);}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$productId = $parameters->getParameter('productId');
		if ($productId)
		{
			/* @var $commerceServices \Rbs\Commerce\Services\CommerceServices */
			$commerceServices = $event->getParam('commerceServices');
			$documentManager = $event->getDocumentServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$attributes['product'] = $product;
				$attributes['title'] = $product->getTitle();
				return 'product.twig';
			}
		}
		return null;
	}
}