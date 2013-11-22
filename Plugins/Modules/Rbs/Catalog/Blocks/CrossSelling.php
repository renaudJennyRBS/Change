<?php
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\CrossSelling
 */
class CrossSelling extends Block
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
		$parameters->addParameterMeta('title');
		$parameters->addParameterMeta('productId');
		$parameters->addParameterMeta('crossSellingType', 'ACCESSORIES');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('itemsPerSlide', 3);
		$parameters->addParameterMeta('slideCount');
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
		$crossSellingType = $parameters->getParameter('crossSellingType');

		if ($productId && $crossSellingType)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$productManager = $commerceServices->getProductManager();

			$rows = array();
			$product = $documentManager->getDocumentInstance($productId, 'Rbs_Catalog_Product');

			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$csParameters = array();
				$csParameters['crossSellingType'] = $crossSellingType;
				$rows = $productManager->getCrossSellingForProduct($product, $csParameters);
			}

			$attributes['rows'] = $rows;
			$attributes['itemsPerSlide'] = $parameters->getParameter('itemsPerSlide');
			if (count($rows) && isset($attributes['itemsPerSlide']))
			{
				$attributes['slideCount'] = ceil(count($rows)/$attributes['itemsPerSlide']);
			}

			return 'product-list-slider.twig';
		}
		return null;
	}

}