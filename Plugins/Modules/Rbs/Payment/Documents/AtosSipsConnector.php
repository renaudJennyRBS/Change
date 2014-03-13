<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Payment\Documents\AtosSipsConnector
 */
class AtosSipsConnector extends \Compilation\Rbs\Payment\Documents\AtosSipsConnector
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('httpInfos', [$this, 'onDefaultHttpInfos'], 5);
		$eventManager->attach([Event::EVENT_CREATE, Event::EVENT_UPDATE], [$this, 'onUpdateServerFile'], 5);
	}

	/**
	 * @param Event $event
	 */
	public function onUpdateServerFile(Event $event)
	{
		$sipsConnector = $event->getDocument();
		if ($sipsConnector instanceof AtosSipsConnector)
		{
			$modifiedProperties = $sipsConnector->getModifiedPropertyNames();
			if (count(array_intersect($modifiedProperties, ['merchantId', 'merchantCountry', 'tpeParmcomContent', 'tpeCertifContent'])))
			{
				$sipsConnector->generateServerFile();
			}
		}
	}

	/**
	 * @api
	 */
	public function generateServerFile()
	{
		$merchantId = $this->getMerchantId();
		$merchantCountry = $this->getMerchantCountry();

		if ($merchantId && $merchantCountry)
		{
			$tpeParmcomContent = $this->getTpeParmcomContent();
			if ($tpeParmcomContent)
			{
				$filePath = $this->getDataDirectory() . '/parmcom.' . $merchantId;
				\Change\Stdlib\File::write($filePath, $tpeParmcomContent);
			}

			$tpeCertifContent = $this->getTpeCertifContent();
			if ($tpeCertifContent)
			{
				$tpeCertifContent = $this->getTpeCertifContent();
				$filePath = $this->getDataDirectory() . '/certif.'.$merchantCountry.'.'.$merchantId.'.php';
				\Change\Stdlib\File::write($filePath, $tpeCertifContent);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultHttpInfos(Event $event)
	{
		$httpEvent = $event->getParam('httpEvent');

		/** @var $sipsConnector AtosSipsConnector */
		$sipsConnector = $event->getDocument();
		if ($httpEvent instanceof \Change\Http\Web\Event)
		{
			$transactionId = $httpEvent->getRequest()->getPost('transactionId', $httpEvent->getRequest()->getQuery('transactionId'));
			$transaction = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($transactionId);
			if ($transaction instanceof Transaction)
			{
				$urlManager = $httpEvent->getUrlManager();
				$oldAbsoluteUrl = $urlManager->getAbsoluteUrl();
				$urlManager->setAbsoluteUrl(true);

				$httpInfos = $event->getParam('httpInfos', []);
				$requestForm = new \Rbs\Payment\AtosSips\Request();
				$requestForm->setMerchantId($sipsConnector->getMerchantId());


				$requestForm->setMerchantCountry($sipsConnector->getMerchantCountry());
				$requestForm->setPathfile($sipsConnector->getPathFileAbsolutePath());
				$requestForm->setBinPathFile($sipsConnector->getRequestBinaryPath());
				list($amount, $currencyCode) = (new \Rbs\Payment\AtosSips\CurrencyConverter())->toParams($transaction->getAmount(), $transaction->getCurrencyCode());
				$requestForm->setAmount($amount);
				$requestForm->setCurrencyCode($currencyCode);
				$requestForm->setTransactionId(str_pad(strval($transaction->getId() % 1000000), 6, '0', STR_PAD_LEFT));

				$requestForm->setReturnContext($transaction->getId());
				$requestForm->setNormalReturnUrl($urlManager->getAjaxURL('Rbs_Payment', 'AtosConnectorReturn', ['connectorId' => $sipsConnector->getId()]));
				$requestForm->setAutomaticResponseUrl($urlManager->getAjaxURL('Rbs_Payment', 'AtosConnectorReturn', ['connectorId' => $sipsConnector->getId(), 'automatic' => 1]));
				$requestForm->setCancelReturnUrl($urlManager->getAjaxURL('Rbs_Payment', 'AtosConnectorReturn', ['connectorId' => $sipsConnector->getId()]));

				$urlManager->setAbsoluteUrl($oldAbsoluteUrl);
				list ($stat, $error, $buffer) = $requestForm->encodeRequest();
				if (intval($stat) === 0)
				{
					$httpInfos['html'] = $buffer;
				}
				else
				{
					$httpInfos['html'] = $error . ' ' . $buffer;
				}
				$event->setParam('httpInfos', $httpInfos);
			}
		}

	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return string|null
	 */
	public function getPaymentReturnTemplate($transaction)
	{
		return parent::getPaymentReturnTemplate($transaction);
	}

	/**
	 * @return string
	 */
	public function getDataDirectory()
	{
		$application = $this->getApplication();
		return $application->getWorkspace()->composeAbsolutePath($application->getConfiguration()
			->getEntry('Rbs/Payment/AtosSips/DataDirectory'));
	}

	/**
	 * @return string
	 */
	public function getPathFileAbsolutePath()
	{
		return $this->getApplication()->getWorkspace()->composePath($this->getDataDirectory(), 'pathfile');
	}

	/**
	 * @return string
	 */
	public function getRequestBinaryPath()
	{
		$app =  $this->getApplication();
		return $app->getWorkspace()->composeAbsolutePath($app->getConfiguration()->getEntry('Rbs/Payment/AtosSips/RequestBinary'));
	}

	/**
	 * @return string
	 */
	public function getResponseBinaryPath()
	{
		$app =  $this->getApplication();
		return $app->getWorkspace()->composeAbsolutePath($app->getConfiguration()->getEntry('Rbs/Payment/AtosSips/ResponseBinary'));
	}

	/**
	 * @return boolean
	 */
	public function isForTest()
	{
		return $this->getMerchantId() === '011223344551111';
	}
}
