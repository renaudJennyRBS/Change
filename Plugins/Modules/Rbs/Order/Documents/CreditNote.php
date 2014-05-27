<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\CreditNote
 */
class CreditNote extends \Compilation\Rbs\Order\Documents\CreditNote
{
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
	}
}
