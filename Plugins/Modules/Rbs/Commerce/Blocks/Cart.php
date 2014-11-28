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
 * @name \Rbs\Commerce\Blocks\Cart
 */
class Cart extends Block
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
		$parameters->addParameterMeta('cartIdentifier');
		$parameters->addParameterMeta('imageFormats', 'cartItem,detailThumbnail,shortCartItem');
		$this->initCommerceContextParameters($parameters);
		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		if ($cartIdentifier !== null)
		{
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if (!$cart)
			{
				$cartIdentifier = null;
			}
			else
			{
				$documentManager = $event->getApplicationServices()->getDocumentManager();
				/** @var \Rbs\Store\Documents\WebStore $webStore */
				$webStore = $documentManager->getDocumentInstance($cart->getWebStoreId());
				$this->setDetailedCommerceContextParameters($webStore, $cart->getBillingArea(), $cart->getZone(),
					$cart->getPriceTargetIds(), $parameters );
			}
		}
		$parameters->setParameterValue('cartIdentifier', $cartIdentifier);
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
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$context = $this->populateContext($event->getApplication(), $documentManager, $parameters);
			$section = $event->getParam('section');
			if ($section)
			{
				$context->setSection($section);
			}
			$context->setPage($event->getParam('page'));
			$cartData = $commerceServices->getCartManager()->getCartData($cartIdentifier, $context->toArray());
			$attributes['cartData'] = $cartData;
		}
		return 'cart.twig';
	}

	/**
	 * @param \Change\Application $application
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param Parameters $parameters
	 * @return \Change\Http\Ajax\V1\Context
	 */
	protected function populateContext($application, $documentManager, $parameters)
	{
		$context = new \Change\Http\Ajax\V1\Context($application, $documentManager);
		$context->setDetailed(true);
		$context->setVisualFormats($parameters->getParameter('imageFormats'));
		$context->setURLFormats(['canonical']);
		return $context;
	}
}