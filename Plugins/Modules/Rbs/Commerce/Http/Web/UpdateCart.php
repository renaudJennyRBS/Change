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
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			if ($cart)
			{
				$request = $event->getRequest();
				$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());
				foreach ($arguments as $key => $value)
				{
					switch ($key)
					{
						case 'userId':
							$value = intval($value);
							$cart->setUserId($value > 0 ? $value : 0);
							$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($value);
							if ($user instanceof \Rbs\User\Documents\User)
							{
								$cart->setEmail($user->getEmail());
							}
							break;

						case 'email':
							$cart->setEmail($value);
							break;

						case 'address':
							if (!is_array($value))
							{
								$address = null;
							}
							else
							{
								$address = new \Rbs\Geo\Address\BaseAddress($value);
							}
							$cart->setAddress($address);
							break;

						case 'shippingModes':
							$shippingModes = array();
							foreach ($value as $data)
							{
								$shippingModes[] = new \Rbs\Commerce\Process\BaseShippingMode($data);
							}
							$cart->setShippingModes($shippingModes);
							break;

						case 'coupons':
							$coupons = array();
							foreach ($value as $data)
							{
								// Ignore entries without a code.
								if (!isset($data['code']))
								{
									continue;
								}
								// TODO: set title property.
								if (!isset($data['title']))
								{
									$data['title'] = $data['code'];
								}
								$coupons[] = new \Rbs\Commerce\Process\BaseCoupon($data);
							}
							$cart->setCoupons($coupons);
							break;

						default:
							$event->getApplicationServices()->getLogging()->warn(__METHOD__ . ' Unknown key ' . $key);
							break;
					}
				}

				$cartManager->normalize($cart);
				$cartManager->saveCart($cart);

				(new GetCurrentCart())->execute($event);
			}
			else
			{
				throw new \RuntimeException('Unable to get the cart', 999999);
			}
		}
		else
		{
			throw new \RuntimeException('Unable to get CommerceServices', 999999);
		}
	}
}