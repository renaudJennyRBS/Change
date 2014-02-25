<?php
namespace Rbs\Discount\Documents;

/**
 * @name \Rbs\Discount\Documents\Coupon
 */
class Coupon extends \Compilation\Rbs\Discount\Documents\Coupon
{
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');

		/** @var $coupon Coupon */
		$coupon = $event->getDocument();
		if ($restResult instanceof \Change\Http\Rest\Result\DocumentResult)
		{
			$restResult->setProperty('orderProcessId', $coupon->getOrderProcessId());
		}
		elseif ($restResult instanceof \Change\Http\Rest\Result\DocumentLink)
		{
			$restResult->setProperty('orderProcessId', $coupon->getOrderProcessId());
		}
	}

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
				return $value->getCartManager()->isValidFilter($value, $this->getCartFilterData());
			}
			elseif ($value instanceof \Rbs\Order\Documents\Order)
			{
				return true;
			}
		}
		return false;
	}

}