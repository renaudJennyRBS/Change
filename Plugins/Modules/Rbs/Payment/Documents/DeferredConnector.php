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
 * @name \Rbs\Payment\Documents\DeferredConnector
 */
class DeferredConnector extends \Compilation\Rbs\Payment\Documents\DeferredConnector
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('httpInfos', [$this, 'onDefaultHttpInfos'], 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultHttpInfos(Event $event)
	{
		$httpInfos = $event->getParam('httpInfos',[]);
		$httpInfos['directiveName'] = 'rbs-commerce-payment-connector-deferred';
		$event->setParam('httpInfos', $httpInfos);
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return string|null
	 */
	public function getPaymentReturnTemplate($transaction)
	{
		if ($transaction->getProcessingStatus() == Transaction::STATUS_PROCESSING)
		{
			return 'Rbs_Commerce/Blocks/paymentReturn/deferred-processing.twig';
		}
		return parent::getPaymentReturnTemplate($transaction);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultUpdateRestResult(Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $restResult \Change\Http\Rest\V1\Resources\DocumentLink|\Change\Http\Rest\V1\Resources\DocumentResult */
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof DocumentResult)
		{
			$i18n = $event->getApplicationServices()->getI18nManager();
			$transactionModel = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Payment_Transaction');
			$restResult->setProperty('substitutions', [
				'amount' => $i18n->trans($transactionModel->getProperty('amount')->getLabelKey(), ['ucf']),
				'transactionId' => $i18n->trans($transactionModel->getLabelKey(), ['ucf'])
			]);
		}
	}
}
