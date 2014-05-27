<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Documents;

use Change\Documents\Events\Event;
use Change\Http\Rest\V1\Resources\DocumentLink;
use Change\Http\Rest\V1\Resources\DocumentResult;

/**
 * @name \Rbs\Payment\Documents\Transaction
 */
class Transaction extends \Compilation\Rbs\Payment\Documents\Transaction
{
	const STATUS_INITIATED = 'initiated';
	const STATUS_PROCESSING = 'processing';
	const STATUS_SUCCESS = 'success';
	const STATUS_FAILED = 'failed';

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return strval($this->getId());
	}

	/**
	 * @param string $label
	 * @return \Rbs\Payment\Documents\Transaction
	 */
	public function setLabel($label)
	{
		// Do nothing.
		return $this;
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $restResult \Change\Http\Rest\V1\Resources\DocumentLink|\Change\Http\Rest\V1\Resources\DocumentResult */
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof DocumentLink || $restResult instanceof DocumentResult)
		{
			/* @var $transaction \Rbs\Payment\Documents\Transaction */
			$transaction = $event->getDocument();
			$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);

			$formattedAmount = $nf->formatCurrency($transaction->getAmount(), $transaction->getCurrencyCode());
			$restResult->setProperty('formattedAmount', $formattedAmount);

			$i18n = $event->getApplicationServices()->getI18nManager();
			$formattedStatus = $i18n->trans('m.rbs.payment.admin.transaction_status_' . $transaction->getProcessingStatus());
			$restResult->setProperty('formattedProcessingStatus', $formattedStatus);
		}
		if ($restResult instanceof DocumentResult)
		{
			/* @var $transaction \Rbs\Payment\Documents\Transaction */
			$transaction = $event->getDocument();

			$status = $transaction->getProcessingStatus();
			if ($status === self::STATUS_INITIATED  || $status === self::STATUS_PROCESSING)
			{
				$restResult->addAction(new \Change\Http\Rest\V1\Resources\DocumentActionLink($restResult->getUrlManager(), $transaction, 'validatePayment'));
				$restResult->addAction(new \Change\Http\Rest\V1\Resources\DocumentActionLink($restResult->getUrlManager(), $transaction, 'refusePayment'));
			}
		}
	}
}