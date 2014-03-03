<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Http\Web;

/**
 * @name \Rbs\Payment\Http\Web\DeferredConnectorReturnSuccess
 */
class DeferredConnectorReturnSuccess extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		if (!isset($arguments['connectorId']) || !isset($arguments['transactionId']))
		{
			throw new \RuntimeException('Invalid parameters', 999999);
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$connector = $documentManager->getDocumentInstance($arguments['connectorId']);
		$transaction = $documentManager->getDocumentInstance($arguments['transactionId']);
		if (!($connector instanceof \Rbs\Payment\Documents\DeferredConnector))
		{
			throw new \RuntimeException('Invalid connector: ' . $connector, 999999);
		}
		if (!($transaction instanceof \Rbs\Payment\Documents\Transaction))
		{
			throw new \RuntimeException('Invalid transaction: ' . $transaction, 999999);
		}

		$tm = $event->getApplicationServices()->getTransactionManager();
		if ($transaction->getProcessingStatus() === \Rbs\Payment\Documents\Transaction::STATUS_INITIATED)
		{
			try
			{
				$tm->begin();

				$transaction->setConnector($connector);
				$this->setPaymentReturnMessage($event, $connector, $transaction);

				if (!$connector->getAutoValidatePayment())
				{
					$transaction->setProcessingStatus(\Rbs\Payment\Documents\Transaction::STATUS_PROCESSING);
				}
				else
				{
					$transaction->setProcessingStatus(\Rbs\Payment\Documents\Transaction::STATUS_SUCCESS);
					$transaction->setProcessingIdentifier('AUTO');
					$transaction->setProcessingDate(new \DateTime());
				}
				$transaction->save();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}

			$commerceServices = $event->getServices('commerceServices');
			if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
			{
				throw new \RuntimeException('Unable to get CommerceServices', 999999);
			}

			$commerceServices->getPaymentManager()->handleProcessingForTransaction($transaction);
			if ($connector->getAutoValidatePayment())
			{
				$commerceServices->getPaymentManager()->handleSuccessForTransaction($transaction);
			}
		}

		$pathRuleManager = $event->getApplicationServices()->getPathRuleManager();
		$data = array('redirectURL' => $this->getRedirectURL($transaction, $documentManager, $pathRuleManager));
		$result = $this->getNewAjaxResult($data);
		$event->setResult($result);
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param \Change\Http\Web\PathRuleManager $pathRuleManager
	 * @return string|null
	 */
	protected function getRedirectURL($transaction, $documentManager, $pathRuleManager)
	{
		$contextData = $transaction->getContextData();
		$website = isset($contextData['websiteId']) ? $documentManager->getDocumentInstance($contextData['websiteId']) : null;
		$LCID = isset($contextData['LCID']) ? $contextData['LCID'] : null;
		$function = isset($contextData['returnSuccessFunction']) ? $contextData['returnSuccessFunction'] : null;
		if ($website instanceof \Change\Presentation\Interfaces\Website && $LCID && $function)
		{
			$params = array('transactionId' => $transaction->getId());
			$urlManager = $website->getUrlManager($LCID);
			$urlManager->setPathRuleManager($pathRuleManager);
			$uri = $urlManager->getByFunction($function, $website, $params, $LCID);
			if ($uri)
			{
				return $uri->toString();
			}
		}
		return null;
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @param \Rbs\Payment\Documents\DeferredConnector $connector
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return \Rbs\Commerce\CommerceServices
	 */
	protected function setPaymentReturnMessage(\Change\Http\Web\Event $event, $connector, $transaction)
	{
		$richTextContext = array('website' => $event->getUrlManager()->getWebsite());
		$richTextManager = $event->getApplicationServices()->getRichTextManager();
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$priceManager = $commerceServices->getPriceManager();
		$instructions = $connector->getCurrentLocalization()->getInstructions();
		$substitutions = [
			'transactionId' => $transaction->getId(),
			'amount' => $priceManager->formatValue($transaction->getAmount(), $transaction->getCurrencyCode())
		];
		$instructions->setRawText(\Change\Stdlib\String::getSubstitutedString($instructions->getRawText(), $substitutions));

		$processingData = $transaction->getProcessingData();
		$processingData['PaymentReturn_Message'] = $richTextManager->render($instructions, 'Website', $richTextContext);
		$transaction->setProcessingData($processingData);
		return $commerceServices;
	}
}