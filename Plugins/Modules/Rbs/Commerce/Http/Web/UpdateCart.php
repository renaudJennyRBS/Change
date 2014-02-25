<?php
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\UpdateCart
 */
class UpdateCart extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			throw new \RuntimeException('Unable to get GenericServices', 999999);
		}

		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof \Rbs\Commerce\CommerceServices))
		{
			throw new \RuntimeException('Unable to get CommerceServices', 999999);
		}

		$cartManager = $commerceServices->getCartManager();
		$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
		$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
		if (!$cart)
		{
			throw new \RuntimeException('Unable to get the cart', 999999);
		}

		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
		$validKeys = ['lineQuantities', 'userId', 'email', 'address', 'shippingModes', 'coupons'];
		if (count(array_intersect($validKeys, array_keys($arguments))) == 0)
		{
			(new GetCurrentCart())->execute($event);
			return;
		}

		if ($cart->isLocked())
		{
			$cart = $cartManager->getUnlockedCart($cart);
		}

		if (isset($arguments['lineQuantities']))
		{
			foreach ($arguments['lineQuantities'] as $data)
			{
				if (!isset($data['key']) || !isset($data['quantity']))
				{
					continue;
				}
				elseif ($data['quantity'] > 0)
				{
					$cartManager->updateLineQuantityByKey($cart, strval($data['key']), $data['quantity']);
				}
				else
				{
					$cartManager->removeLineByKey($cart, strval($data['key']));
				}
			}
		}

		if (isset($arguments['userId']))
		{
			$value = intval($arguments['userId']);
			$cart->setUserId($value > 0 ? $value : 0);
			$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($value);
			if ($user instanceof \Rbs\User\Documents\User)
			{
				$cart->setEmail($user->getEmail());
			}
		}

		if (isset($arguments['email']))
		{
			$cart->setEmail($arguments['email']);
		}

		if (isset($arguments['address']))
		{
			if (!is_array($arguments['address']) || !count($arguments['address']))
			{
				$address = null;
			}
			else
			{
				$address = new \Rbs\Geo\Address\BaseAddress($arguments['address']);
				$address->setLines($genericServices->getGeoManager()->getFormattedAddress($address));
			}
			$cart->setAddress($address);
		}

		if (isset($arguments['shippingModes']))
		{
			$shippingModes = array();
			foreach ($arguments['shippingModes'] as $data)
			{
				$mode = new \Rbs\Commerce\Process\BaseShippingMode($data);
				$address = $mode->getAddress();
				if ($address)
				{
					$address->setLines($genericServices->getGeoManager()->getFormattedAddress($address));
				}
				$shippingModes[] = $mode;
			}
			$cart->setShippingModes($shippingModes);
		}

		if (isset($arguments['coupons']))
		{
			$cart->removeAllCoupons();
			foreach ($arguments['coupons'] as $data)
			{
				// Ignore entries without a code.
				if (!isset($data['code']) || \Change\Stdlib\String::isEmpty($data['code']))
				{
					continue;
				}
				$couponCode = $data['code'];
				if (!$cart->getCouponByCode($couponCode)) {
					$cart->appendCoupon(new \Rbs\Commerce\Process\BaseCoupon($data));
				}
			}
		}

		$cartManager->normalize($cart);
		$cartManager->saveCart($cart);

		$context = $commerceServices->getContext();
		if ($context->getCartIdentifier() !== $cart->getIdentifier())
		{
			$context->setCartIdentifier($cart->getIdentifier());
			$context->save();
		}

		(new GetCurrentCart())->execute($event);
	}
}