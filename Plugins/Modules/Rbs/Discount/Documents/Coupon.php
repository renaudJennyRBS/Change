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
}