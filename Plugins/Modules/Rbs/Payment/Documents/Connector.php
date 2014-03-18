<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Documents;

/**
 * @name \Rbs\Payment\Documents\Connector
 */
class Connector extends \Compilation\Rbs\Payment\Documents\Connector
{
	/**
	 * @param \Rbs\Commerce\Cart\Cart|\Rbs\Order\Documents\Order $value
	 * @param array $options
	 * @return boolean
	 */
	public function isCompatibleWith($value, array $options = null)
	{
		if ($this->activated())
		{
			$filters = new \Rbs\Commerce\Filters\Filters($this->getApplication());
			return $filters->isValid($value, $this->getCartFilterData(), $options);
		}
		return false;
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return string|null
	 */
	public function getPaymentReturnTemplate($transaction)
	{
		switch ($transaction->getProcessingStatus())
		{
			case Transaction::STATUS_SUCCESS:
				return 'Rbs_Commerce/Blocks/paymentReturn/default-success.twig';

			case Transaction::STATUS_FAILED:
				return 'Rbs_Commerce/Blocks/paymentReturn/default-failed.twig';

			case Transaction::STATUS_PROCESSING:
				return 'Rbs_Commerce/Blocks/paymentReturn/default-processing.twig';

			default:
				return null;
		}
	}
}
