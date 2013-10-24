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
		$commerceServices = $event->getServices('commerceServices');
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

			$webStore = $commerceServices->getWebStore();
			if (isset($args['webStoreId']))
			{
				$webStore = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($args['webStoreId'], 'Rbs_Store_WebStore');
			}

			$zone = (isset($args['zone'])) ? strval($args['zone']) : null;
			$context = isset($args['context']) && is_array($args['context']) ? $args['context'] : array();
			$cart = $commerceServices->getCartManager()->getNewCart($webStore, $billingArea, $zone, $context);
			$event->setParam('cartIdentifier', $cart->getIdentifier());

			if (isset($args['ownerId']) || isset($args['lines']))
			{
				$this->populateCart($commerceServices, $cart, $args);
				$commerceServices->getCartManager()->saveCart($cart);
			}


			(new GetCart())->execute($event);

			$result = $event->getResult();
			if ($result instanceof CartResult)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_201);
			}
		}
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param \Rbs\Commerce\Interfaces\Cart $cart
	 * @param array $cartData
	 */
	protected function populateCart($commerceServices, $cart, $cartData)
	{
		if (isset($cartData['ownerId']))
		{
			$cart->setOwnerId($cartData['ownerId']);
		}
		elseif (array_key_exists('ownerId', $cartData))
		{
			$cart->setOwnerId(null);
		}

		if (isset($cartData['lines']) && is_array($cartData['lines']))
		{
			$cm = $commerceServices->getCartManager();
			$cart->removeAllLines();
			foreach ($cartData['lines'] as $lineData)
			{
				$configLine = new \Rbs\Commerce\Cart\CartLineConfig($commerceServices, $lineData);
				$cm->addLine($cart, $configLine, $configLine->getQuantity());
			}
		}
	}
}