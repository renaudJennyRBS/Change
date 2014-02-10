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
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
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
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$productPresentation = $product->getPresentation($commerceServices, $parameters->getParameter('webStoreId'), $event->getUrlManager());
				$productPresentation->evaluate();
				$attributes['productPresentation'] = $productPresentation;

				return 'product.twig';

				/*$attributes['product'] = $product;
				$attributes['canonicalUrl'] = $event->getUrlManager()->getCanonicalByDocument($product)->toString();

				// Cart line configs.
				$productPresentation = $product->getPresentation($commerceServices, $parameters->getParameter('webStoreId'), $event->getUrlManager());
				if ($productPresentation)
				{
					$productPresentation->evaluate();
					$attributes['productPresentation'] = $productPresentation;
				}
				$attributes['attributesConfig'] = $commerceServices->getAttributeManager()->getProductAttributesConfiguration('specifications', $product);

				if ($product->getVariantGroup())
				{
					$variantConfiguration = $commerceServices->getAttributeManager()->buildVariantConfiguration($product->getVariantGroup(), true);
					$attributes['axes'] = $variantConfiguration;
					$axesNames = $this->getAxesNames($product->getVariantGroup(), $documentManager);
					$attributes['axesNames'] = $axesNames;
					$attributes['variantGroup'] = $product->getVariantGroup();
					return 'product-variant.twig';
				}
				else
				{
					return 'product.twig';
				}*/
			}
		}
		return null;
	}


}