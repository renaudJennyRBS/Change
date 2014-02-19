<?php
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
			if ($value instanceof \Rbs\Commerce\Cart\Cart)
			{
				$paymentAmountWithTaxes = $value->getPaymentAmountWithTaxes();
				if ($this->getMinAmount() !== null && $paymentAmountWithTaxes < $this->getMinAmount())
				{
					return false;
				}
				if ($this->getMaxAmount() !== null && $paymentAmountWithTaxes > $this->getMaxAmount())
				{
					return false;
				}
				return true;
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				$paymentAmountWithTaxes = $value->getPaymentAmountWithTaxes();
				if ($this->getMinAmount() !== null && $paymentAmountWithTaxes < $this->getMinAmount())
				{
					return false;
				}
				if ($this->getMaxAmount() !== null && $paymentAmountWithTaxes > $this->getMaxAmount())
				{
					return false;
				}
				return true;
			}
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
