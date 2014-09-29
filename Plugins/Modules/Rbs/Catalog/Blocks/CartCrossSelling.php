<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\CartCrossSelling
 */
class CartCrossSelling extends Block
{

	use \Rbs\Commerce\Blocks\Traits\ContextParameters;

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
		$parameters->addParameterMeta('itemsPerSlide', 3);
		$parameters->addParameterMeta('slideCount');

		$this->initCommerceContextParameters($parameters);
		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters->addParameterMeta('cartIdentifier');

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$parameters->setParameterValue('cartIdentifier', $commerceServices->getContext()->getCartIdentifier());

		$cart = null;
		if ($parameters->getParameter('cartIdentifier') !== null)
		{
			$cart = $commerceServices->getCartManager()->getCartByIdentifier($parameters->getParameter('cartIdentifier'));
			if (!$cart)
			{
				$parameters->setParameterValue('cartIdentifier', null);
			}
		}

		if ($cart)
		{
			/** @var \Rbs\Store\Documents\WebStore $webStore */
			$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
			$this->setDetailedCommerceContextParameters($webStore, $cart->getBillingArea(), $cart->getZone(), $parameters);
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
				$csParameters['urlManager'] = $event->getUrlManager();
				$csParameters['crossSellingType'] = $crossSellingType;
				$csParameters['productChoiceStrategy'] = $productChoiceStrategy;
				$csParameters['webStoreId'] = $parameters->getParameter('webStoreId');
				$csParameters['billingAreaId'] = $parameters->getParameter('billingAreaId');
				$csParameters['zone'] = $parameters->getParameter('zone');
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
