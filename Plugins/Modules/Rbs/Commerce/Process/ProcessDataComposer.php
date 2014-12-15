<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\ProcessDataComposer
 */
class ProcessDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Commerce\Documents\Process
	 */
	protected $process;

	/**
	 * @var \Rbs\Commerce\Process\ProcessManager
	 */
	protected $processManager;

	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;


	function __construct(\Change\Events\Event $event)
	{
		$this->process = $event->getParam('process');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->processManager = $commerceServices->getProcessManager();
		$this->cartManager = $commerceServices->getCartManager();
	}

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
		if (!($this->process instanceof \Rbs\Commerce\Documents\Process))
		{
			return;
		}
		$process = $this->process;
		$context = $this->getSubContext();
		$taxBehavior = $process->getTaxBehavior();

		$this->dataSets['common'] =  ['id' => $process->getId(), 'taxBehavior' => $taxBehavior];
		$this->dataSets['shippingZone'] = null;
		$this->dataSets['taxesZones'] = null;

		if ($taxBehavior == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_BEFORE_PROCESS ||
			$taxBehavior == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_DURING_PROCESS)
		{
			$cartId = isset($this->data['cartId']) ? $this->data['cartId'] : null;
			$cart = $cartId ? $this->cartManager->getCartByIdentifier($cartId) : null;
			if ($cart)
			{
				if ($taxBehavior == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_BEFORE_PROCESS)
				{
					$this->dataSets['shippingZone'] = $cart->getZone();
				}
				elseif ($taxBehavior == \Rbs\Commerce\Documents\Process::TAX_BEHAVIOR_DURING_PROCESS)
				{
					$billingArea = $cart->getBillingArea();
					if ($billingArea)
					{
						$zones = [];
						foreach ($billingArea->getTaxes() as $tax)
						{
							$taxArray = $tax->toArray();
							if (isset($taxArray['rates']) && is_array($taxArray['rates'])) {
								foreach ($taxArray['rates'] as $zonesRate)
								{
									$zones = array_merge($zones, array_keys($zonesRate));
								}
							}
						}
						$this->dataSets['taxesZones'] = array_values(array_unique($zones));
					}
				}
			}
		}


		$shippingModes = $this->processManager->getCompatibleShippingModes($process, $context);
		foreach ($shippingModes as $shippingMode)
		{
			$shippingModeData = $this->processManager->getShippingModeData($shippingMode, $context);
			$this->dataSets['shippingModes'][$shippingMode->getCategory()][] = $shippingModeData;
		}

		foreach ($process->getPaymentConnectors() as $paymentConnector)
		{
			if (!$paymentConnector->activated())
			{
				continue;
			}
			$paymentConnectorData = $this->processManager->getPaymentConnectorData($paymentConnector, $context);
			if (count($paymentConnectorData)) {
				$category = $paymentConnectorData['common']['category'];
				$this->dataSets['paymentConnectors'][$category][] = $paymentConnectorData;
			}
		}
	}

	/**
	 * @return array
	 */
	protected function getSubContext()
	{
		return ['visualFormats' => $this->visualFormats, 'URLFormats' => $this->URLFormats,
			'website' => $this->website, 'websiteUrlManager' => $this->websiteUrlManager, 'section' => $this->section,
			'data' => $this->data, 'detailed' => true];
	}
} 