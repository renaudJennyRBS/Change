<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Order\Documents\CreditNote
 */
class CreditNote extends \Compilation\Rbs\Order\Documents\CreditNote
{
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_CREATE, [$this, 'onDefaultCreate'], 5);
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultCreate(Event $event)
	{
		if ($event->getDocument() !== $this) {
			return;
		}
		if ($this->getAmountNotApplied() === null)
		{
			$this->setAmountNotApplied($this->getAmount());
		}

		if ($this->getCode() === null)
		{
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$this->setCode($commerceServices->getProcessManager()->getNewCode($this));
			}
		}
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getCode() ? $this->getCode() : '[' . $this->getId() . ']';
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);

		/** @var $creditNote CreditNote */
		$creditNote = $event->getDocument();
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
			$formattedAmount = $nf->formatCurrency($creditNote->getAmount(), $creditNote->getCurrencyCode());
			$restResult->setProperty('formattedAmount', $formattedAmount);
			if ($creditNote->getAmountNotApplied() !== null)
			{
				$formattedAmount = $nf->formatCurrency($creditNote->getAmountNotApplied(), $creditNote->getCurrencyCode());
				$restResult->setProperty('formattedAmountNotApplied', $formattedAmount);
			}
		}
		elseif ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$processingData = $creditNote->getProcessingData();
			$usedIn = [];
			if (is_array($processingData) && count($processingData))
			{
				$nf = new \NumberFormatter($event->getApplicationServices()->getI18nManager()->getLCID(), \NumberFormatter::CURRENCY);
				$targetIdentifier = $creditNote->getTargetIdentifier();
				$parts = explode(':', $targetIdentifier);
				if (count($parts) == 2 && $parts[0] = 'Order')
				{
					$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($parts[1]);
					if ($order)
					{
						$vc = new \Change\Http\Rest\V1\ValueConverter($restResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
						$target = $vc->toRestValue($order, \Change\Documents\Property::TYPE_DOCUMENT);
						$restResult->setProperty('target', $target);
					}
				}
				else
				{
					$target = ['model' => 'Rbs_Commerce_Cart', 'identifier' => $targetIdentifier, 'id' => $targetIdentifier, 'label' => $targetIdentifier];
					$restResult->setProperty('target', $target);
				}

				foreach ($processingData as $targetIdentifier => $amount)
				{
					$parts = explode(':', $targetIdentifier);
					if (count($parts) == 2 && $parts[0] = 'Order')
					{
						$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($parts[1]);
						if ($order) {
							$label = $order->getDocumentModel()->getPropertyValue($order, 'label', $targetIdentifier);
							$vc = new \Change\Http\Rest\V1\ValueConverter($restResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
							$target = $vc->toRestValue($order, \Change\Documents\Property::TYPE_DOCUMENT);
						}
						else
						{
							$target = null;
							$label = $parts[1];
						}
					}
					else
					{
						$target = ['model' => 'Rbs_Commerce_Cart', 'identifier' => $targetIdentifier, 'id' => $targetIdentifier];
						$label = $targetIdentifier;
					}

					$usedIn[] = ['targetIdentifier' => $targetIdentifier, 'amount' => $amount, 'label' => $label,
						'formattedAmount' => $nf->formatCurrency($amount, $creditNote->getCurrencyCode()),
						'target' => $target,
					];
				}
			}
			$restResult->setProperty('usedIn', $usedIn);
		}
	}

	/**
	 * @param $targetIdentifier
	 * @return float
	 */
	public function removeUsageByTargetIdentifier($targetIdentifier)
	{
		$processingData = $this->getProcessingData();
		if (is_array($processingData) && isset($processingData[$targetIdentifier])) {

			$amount = $processingData[$targetIdentifier];
			$this->setAmountNotApplied($this->getAmountNotApplied() - $amount);
			unset($processingData[$targetIdentifier]);
			$this->setProcessingData(count($processingData) ? $processingData : null);
			return $amount;
		}
		return 0.0;
	}

	/**
	 * @param $targetIdentifier
	 * @return float
	 */
	public function getUsageByTargetIdentifier($targetIdentifier)
	{
		$processingData = $this->getProcessingData();
		if (is_array($processingData) && isset($processingData[$targetIdentifier]))
		{
			return $processingData[$targetIdentifier];
		}
		return 0.0;
	}

	/**
	 * @param $targetIdentifier
	 * @param float $amount
	 * @return $this
	 */
	public function setUsageByTargetIdentifier($targetIdentifier, $amount)
	{
		$this->removeUsageByTargetIdentifier($targetIdentifier);
		$processingData = $this->getProcessingData();
		if (!is_array($processingData))
		{
			$processingData = [];
		}
		$processingData[$targetIdentifier] = $amount;
		$this->setAmountNotApplied($this->getAmountNotApplied() + $amount);
		$this->setProcessingData($processingData);
		return $this;
	}

	/**
	 * @param string $oldTargetIdentifier
	 * @param string $newTargetIdentifier
	 * @return $this
	 */
	public function renameTargetIdentifier($oldTargetIdentifier, $newTargetIdentifier)
	{
		$processingData = $this->getProcessingData();
		if (is_array($processingData) && isset($processingData[$oldTargetIdentifier]))
		{
			$processingData[$newTargetIdentifier] = $processingData[$oldTargetIdentifier];
			unset($processingData[$oldTargetIdentifier]);
			$this->setProcessingData($processingData);
		}
		return $this;
	}
}
