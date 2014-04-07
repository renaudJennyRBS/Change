<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Job;

/**
 * @name \Rbs\Commerce\Job\TransactionStatusChanged
 */
class TransactionStatusChanged
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			$event->failed('Commerce services not set');
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$transaction = $documentManager->getDocumentInstance($job->getArgument('transactionId'));
		if (!($transaction instanceof \Rbs\Payment\Documents\Transaction))
		{
			$event->failed('Invalid transaction');
			return;
		}

		if ($job->getArgument('status') == \Rbs\Payment\Documents\Transaction::STATUS_SUCCESS)
		{
			$contextData = $transaction->getContextData();
			if (isset($contextData['from']) && $contextData['from'] == 'cart')
			{
				$cartManager = $commerceServices->getCartManager();
				$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
				if ($cart instanceof \Rbs\Commerce\Cart\Cart)
				{
					// Create the order.
					$commerceServices->getProcessManager()->createOrderFromCart($cart);
				}
			}
		}

		$event->success();
	}
} 