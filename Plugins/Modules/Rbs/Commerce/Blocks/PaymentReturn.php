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
		$parameters->addParameterMeta('confirmationPage', 0);

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
			if ($transaction && $transaction->getConnector())
			{
				$connector = $transaction->getConnector();
				$template = $connector->getPaymentReturnTemplate($transaction);
				if ($template && is_string($template))
				{
					$attributes['transaction'] = $transaction;
					$attributes['connector'] = $connector;
					$attributes['paymentTemplate'] = $template;

					$data = $transaction->getContextData();
					if (isset($data['guestCheckout']) && $data['guestCheckout'] == true &&
						$transaction->getEmail() && !$transaction->getAuthorId())
					{
						$attributes['proposeRegistration'] = true;
						$attributes['email'] = $transaction->getEmail();
					}
				}
			}
		}
		return 'paymentReturn.twig';
	}
}