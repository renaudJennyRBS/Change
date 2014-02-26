<?php
namespace Rbs\Catalog\Blocks;

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
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('billingAreaId');
		$parameters->addParameterMeta('zone');
		$parameters->addParameterMeta('activateZoom', true);
		$parameters->addParameterMeta('attributesDisplayMode', 'table');
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');
		$parameters->addParameterMeta('redirectUrl');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters = $this->setParameterValueForDetailBlock($parameters, $event);

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($parameters->getParameter('displayPrices') === null)
			{
				$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}

			$billingArea = $commerceServices->getContext()->getBillingArea();
			if ($billingArea)
			{
				$parameters->setParameterValue('billingAreaId', $billingArea->getId());
			}

			$zone = $commerceServices->getContext()->getZone();
			if ($zone)
			{
				$parameters->setParameterValue('zone', $zone);
			}
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
			$parameters->setParameterValue('billingAreaId', 0);
			$parameters->setParameterValue('zone', null);
			$parameters->setParameterValue('displayPrices', false);
			$parameters->setParameterValue('displayPricesWithTax', false);
		}

		if (!$parameters->getParameter('redirectUrl'))
		{
			$urlManager = $event->getUrlManager();
			$oldValue = $urlManager->getAbsoluteUrl();
			$urlManager->setAbsoluteUrl(true);
			$uri =  $urlManager->getByFunction('Rbs_Commerce_Cart');
			if ($uri)
			{
				$parameters->setParameterValue('redirectUrl',$uri->normalize()->toString());
			}
			$urlManager->setAbsoluteUrl($oldValue);
		}
		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		if ($document instanceof \Rbs\Catalog\Documents\Product && $document->published())
		{
			return true;
		}
		return false;
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
		$productId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($productId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$catalogManager = $commerceServices->getCatalogManager();
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$finalProduct = $this->getProductToBeDisplayed($product, $catalogManager, $documentManager);

				if ($finalProduct !== null)
				{
					$productPresentation = $finalProduct->getPresentation($commerceServices, $parameters->getParameter('webStoreId'), $event->getUrlManager());
					$productPresentation->evaluate();
					$attributes['productPresentation'] = $productPresentation;
					return 'product.twig';
				}
			}
		}
		return null;
	}

	/**
	 * @param \Rbs\Catalog\Documents\Product $product
	 * @param \Rbs\Catalog\CatalogManager $catalogManager
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Rbs\Catalog\Documents\Product
	 */
	protected function getProductToBeDisplayed($product, $catalogManager, $documentManager)
	{
		// If product is a simple product or is root product of variant or is categorizable.
		if (!$product->getVariantGroup() || $product->hasVariants() ||  $product->getCategorizable())
		{
			return $product;
		}

		// Else you have a product that is a final product of variant.
		// If you have generated intermediate variant.
		if (!$product->getVariantGroup()->mustGenerateOnlyLastVariant())
		{
			// Try to find the intermediate variant that must be used to display product.
			$newProductId = $catalogManager->getVariantProductIdMustBeDisplayedForVariant($product);
			if ($newProductId != null)
			{
				$product = $documentManager->getDocumentInstance($newProductId);
				if ($product instanceof \Rbs\Catalog\Documents\Product)
				{
					return $product;
				}
			}
		}

		// Else try to return the root product of variant.
		return $catalogManager->getRootProductOfVariantGroup($product->getVariantGroup());
	}
}