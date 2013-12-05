<?php
namespace Rbs\Order\Documents;

/**
 * @name \Rbs\Order\Documents\Process
 */
class Process extends \Compilation\Rbs\Order\Documents\Process
{

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Shipping\Documents\Mode[]
	 */
	public function getShippingModes($cart)
	{
		throw new \LogicException('Not implemented');
	}

	/**
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Payment\Documents\Connector[]
	 */
	public function getPaymentConnectors($cart)
	{
		throw new \LogicException('Not implemeted');
	}
}
