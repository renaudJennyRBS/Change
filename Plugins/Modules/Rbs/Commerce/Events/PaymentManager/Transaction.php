<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\PaymentManager;

/**
 * @name \Rbs\Commerce\Events\PaymentManager\Transaction
 */
class Transaction
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function handleProcessing(\Change\Events\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $event->getParam('transaction');
		$contextData = $transaction->getContextData();

		// Update the cart.
		if (isset($contextData['from']) && $contextData['from'] == 'cart')
		{
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				// Set cart as processing.
				if (!$cart->isProcessing())
				{
					$cartManager->startProcessingCart($cart);
				}

				// Remove cart from context.
				$context = $commerceServices->getContext();
				if ($context->getCartIdentifier() == $transaction->getTargetIdentifier())
				{
					$context->setCartIdentifier(null)->save();
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function handleSuccess(\Change\Events\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $event->getParam('transaction');
		$contextData = $transaction->getContextData();

		// Update the cart.
		if (isset($contextData['from']) && $contextData['from'] == 'cart')
		{
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				$cartManager->affectTransactionId($cart, $transaction->getId());
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function handleFailed(\Change\Events\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $event->getParam('transaction');
		$contextData = $transaction->getContextData();

		// Update the cart.
		if (isset($contextData['from']) && $contextData['from'] == 'cart')
		{
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				// TODO: is there anything to do here?
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function handleRegistration(\Change\Events\Event $event)
	{
		$event->getApplication()->getLogging()->fatal(__METHOD__);
		$user = $event->getParam('user');
		$transaction = $event->getParam('transaction');

		if ($user instanceof \Rbs\User\Documents\User && $transaction instanceof \Rbs\Payment\Documents\Transaction)
		{
			$contextData = $transaction->getContextData();
			if (isset($contextData['from']) && $contextData['from'] == 'cart')
			{
				/* @var $commerceServices \Rbs\Commerce\CommerceServices */
				$commerceServices = $event->getServices('commerceServices');
				$cartManager = $commerceServices->getCartManager();
				$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
				if ($cart instanceof \Rbs\Commerce\Cart\Cart)
				{
					// Affect user on cart.
					$cartManager->affectUser($cart, $user);

					// Affect user on order if the cart is already converted.
					if ($cart->getOrderId())
					{
						$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($cart->getOrderId());
						if ($order instanceof \Rbs\Order\Documents\Order)
						{
							$order->setAuthorId($user->getId());
							if (!$order->getOwnerId())
							{
								$order->setOwnerId($user->getId());
							}
							$order->save();
						}
					}
				}
			}
		}
	}
}