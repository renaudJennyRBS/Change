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
		$parameters->addParameterMeta('productId');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('categoryId');
		$parameters->addParameterMeta('activateZoom', true);
		$parameters->addParameterMeta('attributesDisplayMode', 'table');
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

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
		$documentManager = $event->getDocumentServices()->getDocumentManager();
		if ($parameters->getParameter('categoryId') === null)
		{
			$categoryId = $request->getQuery('categoryId');
			if ($categoryId) {$parameters->setParameterValue('categoryId', $categoryId);}
		}
		$category = null;
		if ($parameters->getParameter('categoryId') !== null)
		{
			$category = $documentManager->getDocumentInstance($parameters->getParameter('categoryId'));
			if (!($category instanceof \Rbs\Catalog\Documents\Category))
			{
				$parameters->setParameterValue('categoryId', null);
			}
		}

		if ($parameters->getParameter('webStoreId') === null)
		{
			$webStoreId = $request->getQuery('webStoreId', ($category) ? $category->getWebStoreId() : null);
			if ($webStoreId) {$parameters->setParameterValue('webStoreId', $webStoreId);}
		}
		$webStore = null;
		if ($parameters->getParameter('webStoreId') !== null)
		{
			$webStore = $documentManager->getDocumentInstance($parameters->getParameter('webStoreId'));
			if (!($webStore instanceof \Rbs\Store\Documents\WebStore))
			{
				$parameters->setParameterValue('webStoreId', null);
			}
		}

		if ($webStore !== null && $parameters->getParameter('displayPrices') === null)
		{
			$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
			$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
		}

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
				//TODO
				$attributes['attributesDisplayMode'] = $parameters->getParameter('attributesDisplayMode');
				$attributes['activateZoom'] = $parameters->getParameter('activateZoom');
				$attributes['attributesDisplayMode'] = $parameters->getParameter('attributesDisplayMode');

				$attributes['product'] = $product;
				$attributes['canonicalUrl'] = $event->getUrlManager()->getCanonicalByDocument($product)->toString();

				// Categories.
				$attributes['categories'] = $product->getPublishedCategories($event->getParam('website'));

				// Cart line configs.
				$productPresentation = $product->getPresentation($commerceServices, $parameters->getWebStoreId());
				if ($productPresentation)
				{
					$productPresentation->evaluate();
					$attributes['productPresentation'] = $productPresentation;
				}

				// Attributes.
				$attributePresentation = new \Rbs\Catalog\Std\AttributePresentation($product);
				$attributes['attributesConfig'] = $attributePresentation->getConfiguration('specifications');

				return 'product.twig';
			}
		}
		return null;
	}
}