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
 * @name \Rbs\Commerce\Blocks\PaymentReturn
 */
class PaymentReturn extends Block
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
		$parameters->addParameterMeta('transactionId');
		$parameters->addParameterMeta('transactionStatus');

		$parameters->setLayoutParameters($event->getBlockLayout());

		$request = $event->getHttpRequest();
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$transaction = $documentManager->getDocumentInstance(intval($request->getQuery('transactionId')));
		if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
		{
			$parameters->setParameterValue('transactionId', $transaction->getId());
			$parameters->setParameterValue('transactionStatus', $transaction->getProcessingStatus());
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @throws \RuntimeException
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$transactionId = $parameters->getParameter('transactionId');
		if ($transactionId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			/* @var $transaction \Rbs\Payment\Documents\Transaction */
			$transaction = $documentManager->getDocumentInstance($transactionId);
			$attributes['transaction'] = $transaction;

			$connector = $transaction->getConnector();
			if (!$connector)
			{
				return 'paymentReturn-invalid.twig';
			}
			$attributes['connector'] = $connector;

			$template = $connector->getPaymentReturnTemplate($transaction);
			if ($template === null)
			{
				return 'paymentReturn-invalid.twig';
			}
			elseif (!is_string($template))
			{
				throw new \RuntimeException('Invalid payment template!');
			}
			$attributes['paymentTemplate'] = $template;

			$data = $transaction->getContextData();
			if (isset($data['guestCheckout']) && $data['guestCheckout'] == true && $transaction->getEmail())
			{
				$attributes['proposeRegistration'] = true;
			}
			return 'paymentReturn.twig';
		}
		return 'paymentReturn-invalid.twig';
	}
}