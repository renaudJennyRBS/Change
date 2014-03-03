<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Job;

/**
 * @name \Rbs\Payment\Job\TransactionStatusChanged
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

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			$event->failed('Commerce services not set');
			return;
		}
		$mailManager = $genericServices->getMailManager();

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$transaction = $documentManager->getDocumentInstance($job->getArgument('transactionId'));
		if (!($transaction instanceof \Rbs\Payment\Documents\Transaction))
		{
			$event->failed('Invalid transaction');
			return;
		}

		$status = $job->getArgument('status');
		switch ($status)
		{
			case \Rbs\Payment\Documents\Transaction::STATUS_PROCESSING:
			case \Rbs\Payment\Documents\Transaction::STATUS_SUCCESS:
			case \Rbs\Payment\Documents\Transaction::STATUS_FAILED:
				$commerceServices->getPaymentManager()->sendTransactionStatusChangedMail($transaction, $status, $mailManager);
				break;

			default:
				// Do nothing.
				break;
		}
		$event->success();
	}
} 