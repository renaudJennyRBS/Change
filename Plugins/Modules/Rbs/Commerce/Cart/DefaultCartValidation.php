<?php
namespace Rbs\Commerce\Cart;

/**
* @name \Rbs\Commerce\Cart\DefaultCartValidation
*/
class DefaultCartValidation
{
	/**
	 * Event Params: cart, errors, lockForOwnerId, commerceServices
	 * @param \Zend\EventManager\Event $event
	 */
	public function execute(\Zend\EventManager\Event $event)
	{

		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Interfaces\Cart)
		{
			$i18nManager = $cart->getCommerceServices()->getApplicationServices()->getI18nManager();

			/* @var $errors \ArrayObject */
			$errors = $event->getParam('errors');

			if (!$cart->getWebStoreId())
			{
				$message = $i18nManager->trans('m.rbs.commerce.errors.cart-without-webstore', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			foreach ($cart->getLines() as $line)
			{
				if (!$line->getQuantity())
				{
					$message = $i18nManager->trans('m.rbs.commerce.errors.line-without-quantity', array('ucf'), array('number' => $line->getNumber()));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif (count($line->getItems()) === 0)
				{
					$message = $i18nManager->trans('m.rbs.commerce.errors.line-without-sku', array('ucf'), array('number' => $line->getNumber()));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif ($line->getUnitPriceValue() === null)
				{
					$message = $i18nManager->trans('m.rbs.commerce.errors.line-without-price', array('ucf'), array('number' => $line->getNumber()));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
			}

			$reservations = $cart->getCommerceServices()->getCartManager()->getReservations($cart);
			if (count($reservations))
			{
				$unreserved = $cart->getCommerceServices()->getStockManager()->setReservations($cart->getIdentifier(), $reservations);
				if (count($unreserved))
				{
					$message = $i18nManager->trans('m.rbs.commerce.errors.cart-reservation-error', array('ucf'));
					$err = new CartError($message);
					$errors[] = $err;
				}
			}
			else
			{
				$cart->getCommerceServices()->getStockManager()->unsetReservations($cart->getIdentifier());
			}
		}
	}
}