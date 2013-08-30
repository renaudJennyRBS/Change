<?php
namespace Rbs\Commerce\Http\Rest\Action;

use Rbs\Commerce\Http\Rest\Result\CartResult;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Commerce\Http\Rest\Action\InsertCart
*/
class InsertCart
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$commerceServices = $event->getParam('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\Services\CommerceServices)
		{
			$args = $event->getRequest()->getPost()->toArray();
			if (isset($args['billingArea']))
			{
				$billingArea = $commerceServices->getPriceManager()->getBillingAreaByCode(strval($args['billingArea']));
			}
			else
			{
				$billingArea = null;
			}

			$zone = (isset($args['zone'])) ? strval($args['zone']) : null;
			$context = isset($args['context']) && is_array($args['context']) ? $args['context'] : array();
			$cart = $commerceServices->getCartManager()->getNewCart($billingArea, $zone, $context);
			$event->setParam('cartIdentifier', $cart->getIdentifier());
			(new GetCart())->execute($event);
			$result = $event->getResult();
			if ($result instanceof CartResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
			}
		}
	}
}