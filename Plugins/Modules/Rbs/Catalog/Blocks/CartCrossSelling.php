<?php
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\CartCrossSelling
 */
class CartCrossSelling extends Block
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
		$parameters->addParameterMeta('productChoiceStrategy');
		$parameters->addParameterMeta('crossSellingType');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('itemsPerSlide', 3);
		$parameters->addParameterMeta('slideCount');
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$parameters->setLayoutParameters($event->getBlockLayout());

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if ($parameters->getParameter('cartIdentifier') === null)
		{
			$parameters->setParameterValue('cartIdentifier', $commerceServices->getContext()->getCartIdentifier());
		}

		if ($parameters->getParameter('cartIdentifier') !== null)
		{
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($parameters->getParameter('cartIdentifier'));
			if (!$cart)
			{
				$parameters->setParameterValue('cartIdentifier', null);
			}
		}

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
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$productChoiceStrategy = $parameters->getParameter('productChoiceStrategy');
		$crossSellingType = $parameters->getParameter('crossSellingType');
		$cart = $commerceServices->getCartManager()->getCartByIdentifier($parameters->getParameter('cartIdentifier'));
		if ($cart && $productChoiceStrategy && $crossSellingType)
		{
			$productManager = $commerceServices->getProductManager();

			$rows = array();
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				$csParameters = array();
				$csParameters['crossSellingType'] = $crossSellingType;
				$csParameters['productChoiceStrategy'] = $productChoiceStrategy;
				$rows = $productManager->getCrossSellingForCart($cart, $csParameters);
			}

			$attributes['rows'] = $rows;
			$attributes['itemsPerSlide'] = $parameters->getParameter('itemsPerSlide');
			if (count($rows) && isset($attributes['itemsPerSlide']))
			{
				$attributes['slideCount'] = ceil(count($rows) / $attributes['itemsPerSlide']);
			}

			return 'product-list-slider.twig';
		}
		return null;
	}
}
