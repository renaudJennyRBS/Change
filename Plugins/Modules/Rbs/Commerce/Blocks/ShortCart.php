<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Commerce\Blocks\ShortCart
 */
class ShortCart extends Block
{
	use Traits\ContextParameters;

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
		$this->initCommerceContextParameters($parameters);
		$parameters->setLayoutParameters($event->getBlockLayout());

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		if ($cartIdentifier)
		{
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if ($cart)
			{
				/** @var \Rbs\Store\Documents\WebStore $webStore */
				$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
				$this->setDetailedCommerceContextParameters($webStore, $cart->getBillingArea(), $cart->getZone(), $parameters);
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
		return 'short-cart.twig';
	}
}