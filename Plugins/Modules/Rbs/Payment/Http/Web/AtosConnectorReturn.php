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
 * @name \Rbs\Payment\Http\Web\AtosConnectorReturn
 */
class AtosConnectorReturn extends \Change\Http\Web\Actions\AbstractAjaxAction
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
		$urlManager = $event->getUrlManager();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		$automatic = isset($arguments['automatic']);

		$result = $this->getNewAjaxResult([]);
		$event->setResult($result);

		if (!isset($arguments['connectorId']) || !isset($arguments['DATA']))
		{
			$event->getApplication()->getLogging()->error('AtosConnectorReturn: invalid parameters');
			if (!$automatic) {
				$event->setParam('redirectLocation', $this->getCancelRedirectURL($urlManager));
			}
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$connector = $documentManager->getDocumentInstance($arguments['connectorId']);
		if (!($connector instanceof \Rbs\Payment\Documents\AtosSipsConnector))
		{
			$event->getApplication()->getLogging()->error('AtosConnectorReturn: invalid connector ' . $arguments['connectorId']);
			if (!$automatic) {
				$event->setParam('redirectLocation', $this->getCancelRedirectURL($urlManager));
			}
			return;
		}

		$response = new \Rbs\Payment\AtosSips\Response();
		$response->setBinPathFile($connector->getResponseBinaryPath());
		$response->setPathfile($connector->getPathFileAbsolutePath());
		$response->setMessage($arguments['DATA']);

		$responseData = $response->decode();

		$now = new \DateTime();
		$logDataFile = $connector->getDataDirectory() . '/transactions/' . $now->format('Y-m/d-H-i-s') . uniqid('-') . '.json';
		\Change\Stdlib\File::write($logDataFile, json_encode($responseData));

		$transactionId = isset($responseData['return_context']) ? intval($responseData['return_context']): 0;
		if ($transactionId)
		{
			$transaction = $documentManager->getDocumentInstance($transactionId);
		}
		else
		{
			$transaction = null;
		}

		if (!($transaction instanceof \Rbs\Payment\Documents\Transaction))
		{
			$event->getApplication()->getLogging()->error('AtosConnectorReturn: invalid transaction ' . $transactionId);
			$event->getApplication()->getLogging()->error('AtosConnectorReturn: DATA ' . $arguments['DATA']);
			if (!$automatic) {
				$event->setParam('redirectLocation', $this->getCancelRedirectURL($urlManager));
			}
			return;
		}

		$processingStatus = $transaction->getProcessingStatus();

		$bankResponseCode = isset($responseData['bank_response_code']) ? $responseData['bank_response_code'] : null;

		$tm = $event->getApplicationServices()->getTransactionManager();

		if ($bankResponseCode === '00')
		{
			if ($processingStatus !== \Rbs\Payment\Documents\Transaction::STATUS_SUCCESS)
			{
				try
				{
					$tm->begin();

					$transaction->setConnector($connector);
					$transaction->setProcessingData($responseData);
					$transaction->setProcessingIdentifier($responseData['authorisation_id']);
					$transaction->setProcessingStatus(\Rbs\Payment\Documents\Transaction::STATUS_SUCCESS);
					$transaction->setProcessingDate($now);
					$transaction->save();

					$tm->commit();
				}
				catch (\Exception $e)
				{
					throw $tm->rollBack($e);
				}

				$commerceServices = $event->getServices('commerceServices');
				if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
				{
					$commerceServices->getPaymentManager()->handleProcessingForTransaction($transaction);
					$commerceServices->getPaymentManager()->handleSuccessForTransaction($transaction);
				}
			}

			if (!$automatic) {
				$event->setParam('redirectLocation', $this->getSuccessRedirectURL($transaction, $urlManager));
			}
			return;
		}
		elseif ($transaction->getProcessingStatus() === \Rbs\Payment\Documents\Transaction::STATUS_INITIATED)
		{
			try
			{
				$tm->begin();

				$transaction->setConnector($connector);
				$transaction->setProcessingData($responseData);
				$transaction->setProcessingStatus(\Rbs\Payment\Documents\Transaction::STATUS_FAILED);
				$transaction->save();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}

			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$commerceServices->getPaymentManager()->handleFailedForTransaction($transaction);
			}

			if (!$automatic) {
				$event->setParam('redirectLocation', $this->getCancelRedirectURL($urlManager));
			}
		}
	}


	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return string|null
	 */
	protected function getSuccessRedirectURL($transaction, $urlManager)
	{
		$contextData = $transaction->getContextData();
		$function = isset($contextData['returnSuccessFunction']) ? $contextData['returnSuccessFunction'] : null;
		$uri = null;
		if ($function)
		{
			$params = array('transactionId' => $transaction->getId());
			$uri = $urlManager->getByFunction($function, null, $params);
		}
		if (!$uri)
		{
			$urlManager->setAbsoluteUrl(true);
			$uri = $urlManager->getByPathInfo(null);
		}
		return $uri->normalize()->toString();
	}

	/**
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @return string|null
	 */
	protected function getCancelRedirectURL($urlManager)
	{

		$uri = $urlManager->getByFunction('Rbs_Commerce_Cart');
		if (!$uri)
		{
			$urlManager->setAbsoluteUrl(true);
			$uri = $urlManager->getByPathInfo(null);
		}
		return $uri->normalize()->toString();
	}
}