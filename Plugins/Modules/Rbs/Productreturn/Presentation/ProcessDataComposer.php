<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Productreturn\Presentation;

/**
 * @name \Rbs\Productreturn\Presentation\ProcessDataComposer
 */
class ProcessDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\Productreturn\Documents\Process
	 */
	protected $process;

	/**
	 * @var \Rbs\Productreturn\ReturnManager
	 */
	protected $returnManager;

	/**
	 * @var null|array
	 */
	protected $dataSets = null;

	/**
	 * @param \Change\Events\Event $event
	 */
	public function __construct(\Change\Events\Event $event)
	{
		$this->process = $event->getParam('process');

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$this->setServices($event->getApplicationServices());

		/** @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		$this->orderManager = $commerceServices->getOrderManager();
		$this->catalogManager = $commerceServices->getCatalogManager();
		$this->priceManager = $commerceServices->getPriceManager();
		$this->processManager = $commerceServices->getProcessManager();
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
		if (!$this->process || !$this->process->activated())
		{
			return;
		}
		/** @var \Rbs\Productreturn\Documents\Process $process */
		$process = $this->process;

		$this->dataSets['common'] = [
			'id' => $process->getId()
		];

		$this->generateReasonsDataSet();
		$this->generateReturnModesDataSet();
		$this->generateReshippingModesDataSet();
	}

	protected function generateReasonsDataSet()
	{
		$this->dataSets['reasons'] = [];
		foreach ($this->process->getReasons() as $reason)
		{
			if ($reason instanceof \Rbs\Productreturn\Documents\Reason && $reason->activated())
			{
				$this->dataSets['reasons'][] = $this->generateReasonData($reason);
			}
		}
	}

	/**
	 * @param \Rbs\Productreturn\Documents\Reason $reason
	 * @return array
	 */
	protected function generateReasonData(\Rbs\Productreturn\Documents\Reason $reason)
	{
		$data = [];

		$data['id'] = $reason->getId();
		$data['title'] = $reason->getCurrentLocalization()->getTitle();
		$data['requirePrecisions'] = $reason->getRequirePrecisions();
		$data['requireAttachedFile'] = $reason->getRequireAttachedFile();
		$data['timeLimitAfterReceipt'] = $reason->getTimeLimitAfterReceipt();
		$data['extraTimeAfterShipping'] = $reason->getExtraTimeAfterShipping();

		$message = $reason->getCurrentLocalization()->getTimeoutMessage();
		$data['timeoutMessage'] = trim($this->formatRichText($message));

		$data['processingModes'] = [];
		foreach ($reason->getProcessingModes() as $processingMode)
		{
			if ($processingMode instanceof \Rbs\Productreturn\Documents\ProcessingMode && $processingMode->activated())
			{
				$data['processingModes'][] = $this->generateProcessingModeData($processingMode);
			}
		}

		return $data;
	}

	/**
	 * @param \Rbs\Productreturn\Documents\ProcessingMode $mode
	 * @return array
	 */
	protected function generateProcessingModeData(\Rbs\Productreturn\Documents\ProcessingMode $mode)
	{
		$data = [];

		$data['id'] = $mode->getId();
		$data['title'] = $mode->getCurrentLocalization()->getTitle();
		$data['impliesReshipment'] = $mode->getImpliesReshipment();
		$data['allowVariantSelection'] = $mode->getAllowVariantSelection();
		return $data;
	}

	protected function generateReturnModesDataSet()
	{
		$this->dataSets['returnModes'] = [];
		foreach ($this->process->getReturnModes() as $mode)
		{
			if ($mode instanceof \Rbs\Productreturn\Documents\ReturnMode && $mode->activated())
			{
				$this->dataSets['returnModes'][] = $this->generateReturnModeData($mode);
			}
		}
	}

	/**
	 * @param \Rbs\Productreturn\Documents\ReturnMode $mode
	 * @return array
	 */
	protected function generateReturnModeData(\Rbs\Productreturn\Documents\ReturnMode $mode)
	{
		$data = [];

		$data['id'] = $mode->getId();
		$data['title'] = $mode->getCurrentLocalization()->getTitle();

		$instructions = $mode->getCurrentLocalization()->getInstructions();
		$data['timeoutMessage'] = trim($this->formatRichText($instructions));

		return $data;
	}

	protected function generateReshippingModesDataSet()
	{
		$this->dataSets['reshippingModes'] = [];
		foreach ($this->process->getReshippingModes() as $mode)
		{
			if ($mode instanceof \Rbs\Shipping\Documents\Mode && $mode->activated())
			{
				$this->dataSets['reshippingModes'][] = $this->generateReshippingModeData($mode);
			}
		}
	}

	/**
	 * @param \Rbs\Shipping\Documents\Mode $mode
	 * @return array
	 */
	protected function generateReshippingModeData(\Rbs\Shipping\Documents\Mode $mode)
	{
		$data = [];

		$data['id'] = $mode->getId();
		$data['title'] = $mode->getCurrentLocalization()->getTitle();

		// TODO

		return $data;
	}
} 