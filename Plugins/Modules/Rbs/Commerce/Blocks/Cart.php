<?php
namespace Rbs\Commerce\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Commerce\Blocks\Cart
 */
class Cart extends Block
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
		$parameters->addParameterMeta('cartIdentifier', Property::TYPE_INTEGER, true);
		$parameters->setLayoutParameters($event->getBlockLayout());
		if ($parameters->getParameter('cartIdentifier') === null)
		{
			/* @var $commerceServices \Rbs\Commerce\Services\CommerceServices */
			$commerceServices = $event->getParam('commerceServices');
			$parameters->setParameterValue('cartIdentifier', $commerceServices->getCartIdentifier());
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
			/* @var $commerceServices \Rbs\Commerce\Services\CommerceServices */
			$commerceServices = $event->getParam('commerceServices');
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart && !$cart->isEmpty())
			{
				$attributes['cart'] = $cart;
				return 'cart.twig';
			}
		}
		return 'cart-undefined.twig';
	}
}