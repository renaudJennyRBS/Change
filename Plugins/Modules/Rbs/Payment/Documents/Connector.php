<?php
namespace Rbs\Payment\Documents;

/**
 * @name \Rbs\Payment\Documents\Connector
 */
class Connector extends \Compilation\Rbs\Payment\Documents\Connector
{
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
