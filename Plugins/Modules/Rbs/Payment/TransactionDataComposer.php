<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment;

/**
 * @name \Rbs\Payment\TransactionDataComposer
 */
class TransactionDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Payment\Documents\Transaction
	 */
	protected $transaction;

	/**
	 * @var \Rbs\Commerce\Process\ProcessManager
	 */
	protected $processManager;

	/**
	 * @var \Rbs\Payment\PaymentManager
	 */
	protected $paymentManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	function __construct(\Change\Events\Event $event)
	{
		$this->transaction = $event->getParam('transaction');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->processManager = $commerceServices->getProcessManager();
		$this->paymentManager = $commerceServices->getPaymentManager();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateDataSets()
	{
		$this->dataSets = [];
		if (!$this->transaction)
		{
			return;
		}
		/** @var \Rbs\Payment\Documents\Transaction $transaction */
		$transaction = $this->transaction;

		$this->dataSets['common'] = [
			'id' => $transaction->getId(),
			'email' => $transaction->getEmail(),
			'authorId' => $transaction->getAuthorId(),
			'ownerId' => $transaction->getOwnerId(),
			'targetIdentifier' => $transaction->getTargetIdentifier(),
			'amount' => $transaction->getAmount(),
			'currencyCode' => $transaction->getCurrencyCode(),
			'statusInfos' => $this->paymentManager->getTransactionStatusInfo($transaction),
			'processingIdentifier' => $transaction->getProcessingIdentifier()
		];

		if ($transaction->getProcessingDate())
		{
			$this->dataSets['common']['processingDate'] = $this->formatDate($transaction->getProcessingDate());
		}

		$this->dataSets['context'] = $transaction->getContextData();

		$this->generateConnectorSet();
	}

	// Connector.

	/**
	 * @return array
	 */
	protected function getConnectorContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'detailed' => $this->detailed
		];
	}

	protected function generateConnectorSet()
	{
		$connector = $this->transaction->getConnector();
		if ($connector)
		{
			$connectorContext = $this->getConnectorContext();
			$this->dataSets['connector'] = $this->processManager->getPaymentConnectorData($connector, $connectorContext);
		}
	}
}