<?php
namespace Rbs\Payment\Documents;

/**
 * @name \Rbs\Payment\Documents\Connector
 */
class Connector extends \Compilation\Rbs\Payment\Documents\Connector
{
	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return string|string[]
	 */
	public function getPaymentReturnTemplate($transaction)
	{
		switch ($transaction->getProcessingStatus())
		{
			case Transaction::STATUS_SUCCESS:
				return array('Rbs_Commerce', 'paymentReturn-default-success.twig');

			case Transaction::STATUS_PROCESSING:
				return array('Rbs_Commerce', 'paymentReturn-default-processing.twig');

			default:
				return array('Rbs_Commerce', 'paymentReturn-invalid.twig');
		}
	}
}
