<?php
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;
use Rbs\Catalog\Product\AxisConfiguration;

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
		$parameters->addParameterMeta('activateZoom', true);
		$parameters->addParameterMeta('attributesDisplayMode', 'table');
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');
		$parameters->addParameterMeta('redirectUrl');

		$parameters->setLayoutParameters($event->getBlockLayout());
		if ($parameters->getParameter('productId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Catalog\Documents\Product && $document->published())
			{
				$parameters->setParameterValue('productId', $document->getId());
			}
		}
		else
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($parameters->getParameter('productId'));
			if (!$product instanceof \Rbs\Catalog\Documents\Product || !$product->published())
			{
				$parameters->setParameterValue('productId', null);
			}
		}

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
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$attributes['product'] = $product;
				$attributes['canonicalUrl'] = $event->getUrlManager()->getCanonicalByDocument($product)->toString();

				// Cart line configs.
				$productPresentation = $product->getPresentation($commerceServices, $parameters->getParameter('webStoreId'));
				if ($productPresentation)
				{
					$productPresentation->evaluate();
					$attributes['productPresentation'] = $productPresentation;
				}
				$attributes['attributesConfig'] = $commerceServices->getAttributeManager()->getProductAttributesConfiguration('specifications', $product);

				if ($product->getVariantGroup())
				{
					$variantConfiguration = $commerceServices->getAttributeManager()->buildVariantConfiguration($product->getVariantGroup());
					$attributes['axes'] = $variantConfiguration;
					$axesNames = $this->getAxesNames($product->getVariantGroup(), $documentManager);
					$attributes['axesNames'] = $axesNames;
					$attributes['variantGroup'] = $product->getVariantGroup();
					return 'product-variant.twig';
				}
				else
				{
					return 'product.twig';
				}
			}
		}
		return null;
	}

	/**
	 * @param \Rbs\Catalog\Documents\VariantGroup $variantGroup
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return array
	 */
	public function getAxesNames($variantGroup, $documentManager)
	{
		$axesNames = array();
		$configuration = $variantGroup->getAxesConfiguration();
		if (is_array($configuration) && count($configuration))
		{
			foreach ($configuration as $confArray)
			{
				$conf = (new AxisConfiguration())->fromArray($confArray);
				/* @var $axeAttribute \Rbs\Catalog\Documents\Attribute */
				$axeAttribute = $documentManager->getDocumentInstance($conf->getId());
				if ($axeAttribute)
				{
					$axesNames[] = $axeAttribute->getCurrentLocalization()->getTitle();
				}
			}
		}
		return $axesNames;
	}
}