<?php
namespace Rbs\Commerce\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Commerce\Blocks\Cart
 */
class ShortCart extends Block
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
		$parameters->addParameterMeta('cartIdentifier');
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
			elseif ($parameters->getParameter('displayPrices') === null)
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				$webStore = $documentManager->getDocumentInstance($cart->getWebStoreId());
				if ($webStore instanceof \Rbs\Store\Documents\WebStore)
				{
					$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
					$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
				}
			}
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
		$cartIdentifier = $parameters->getParameter('cartIdentifier');
		if ($cartIdentifier)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart && !$cart->isEmpty())
			{
				$attributes['cart'] = $cart;
				return 'shortCart.twig';
			}
		}
		return 'shortCart-empty.twig';
	}
}