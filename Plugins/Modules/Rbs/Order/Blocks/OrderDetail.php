<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Order\Blocks\OrderDetail
 */
class OrderDetail extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param \Change\Presentation\Blocks\Event $event
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('accessorId');
		$parameters->addParameterMeta('orderId');
		$parameters->addParameterMeta('cartIdentifier');
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$parameters->setLayoutParameters($event->getBlockLayout());
		$parameters->setNoCache();


		$user = $event->getAuthenticationManager()->getCurrentUser();
		$userId = $user->authenticated() ? $user->getId() : null;
		$orderId = $event->getHttpRequest()->getQuery('orderId');
		if ($orderId)
		{
			return $this->parameterizeForOrder($event, $parameters, $userId, $orderId);
		}

		$cartIdentifier = $event->getHttpRequest()->getQuery('cartIdentifier');
		if ($cartIdentifier)
		{
			return $this->parameterizeForCart($event, $parameters, $userId, $cartIdentifier);
		}
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @param integer $userId
	 * @param integer $orderId
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterizeForOrder($event, $parameters, $userId, $orderId)
	{
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$order = $documentManager->getDocumentInstance($orderId);
		if (!($order instanceof \Rbs\Order\Documents\Order))
		{
			return $this->setInvalidParameters($parameters);
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$options = [ 'userId' => $userId, 'order' => $order ];
		if (!$userId || !$commerceServices->getOrderManager()->canViewOrder($options))
		{
			return $this->setInvalidParameters($parameters);
		}
		else
		{
			$parameters->setParameterValue('orderId', $orderId);
			$parameters->setParameterValue('cartIdentifier', '');
			$parameters->setParameterValue('accessorId', $userId);
		}

		$webStore = $documentManager->getDocumentInstance($order->getWebStoreId());
		if ($webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
			$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
		}
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Event $event
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @param integer $userId
	 * @param string $cartIdentifier
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function parameterizeForCart($event, $parameters, $userId, $cartIdentifier)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$cart = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
		if (!($cart instanceof \Rbs\Commerce\Cart\Cart))
		{
			return $this->setInvalidParameters($parameters);
		}

		$options = [ 'userId' => $userId, 'cart' => $cart ];
		if (!$userId || !$commerceServices->getOrderManager()->canViewOrder($options))
		{
			return $this->setInvalidParameters($parameters);
		}
		else
		{
			$parameters->setParameterValue('orderId', 0);
			$parameters->setParameterValue('cartIdentifier', $cartIdentifier);
			$parameters->setParameterValue('accessorId', $userId);
		}

		$webStore = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getWebStoreId());
		if ($webStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
			$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
		}
		return $parameters;
	}

	/**
	 * @param \Change\Presentation\Blocks\Parameters $parameters
	 * @return \Change\Presentation\Blocks\Parameters
	 */
	protected function setInvalidParameters($parameters)
	{
		$parameters->setParameterValue('orderId', 0);
		$parameters->setParameterValue('cartIdentifier', '');
		$parameters->setParameterValue('accessorId', 0);
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
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$parameters = $event->getBlockParameters();

		$orderId = $parameters->getParameter('orderId');
		$cartIdentifier = $parameters->getParameter('cartIdentifier');
		if ($orderId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$order = $documentManager->getDocumentInstance($orderId);
			if (!($order instanceof \Rbs\Order\Documents\Order))
			{
				return null;
			}
		}
		elseif ($cartIdentifier)
		{
			$order = $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier);
			if (!($order instanceof \Rbs\Commerce\Cart\Cart))
			{
				return null;
			}
		}
		else
		{
			return null;
		}

		$options = [ 'withTransactions' => true, 'withShipments' => true ];
		$attributes['order'] = $commerceServices->getOrderManager()->getOrderPresentation($order, $options);
		return 'order-detail.twig';
	}
}