<?php
namespace Rbs\Payment\Documents;

/**
 * @name \Rbs\Payment\Documents\DeferredConnector
 */
class DeferredConnector extends \Compilation\Rbs\Payment\Documents\DeferredConnector
{
	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return string|null
	 */
	public function getPaymentReturnTemplate($transaction)
	{
		if ($transaction->getProcessingStatus() == Transaction::STATUS_PROCESSING)
		{
			return 'Rbs_Commerce/Blocks/paymentReturn/deferred-processing.twig';
		}
		return parent::getPaymentReturnTemplate($transaction);
	}
}
