<?php
namespace Rbs\Commerce\Cart;

/**
 * @name \Rbs\Commerce\Cart\DefaultCartValidation
 */
class DefaultCartValidation
{
	/**
	 * Event Params: cart, errors, lockForOwnerId, commerceServices
	 * @param \Change\Events\Event $event
	 */
	public function execute(\Change\Events\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $commerceServices instanceof \Rbs\Commerce\CommerceServices)
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();

			/* @var $errors \ArrayObject */
			$errors = $event->getParam('errors');

			if (!$cart->getWebStoreId())
			{
				$message = $i18nManager->trans('m.rbs.commerce.front.cart_without_webstore', array('ucf'));
				$err = new CartError($message, null);
				$errors[] = $err;
				return;
			}

			foreach ($cart->getLines() as $line)
			{
				if (!$line->getQuantity())
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_quantity', array('ucf'), array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif (count($line->getItems()) === 0)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_sku', array('ucf'), array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
				elseif ($line->getUnitPriceValue() === null)
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.line_without_price', array('ucf'), array('number' => $line->getIndex() + 1));
					$err = new CartError($message, $line->getKey());
					$errors[] = $err;
				}
			}

			$reservations = $commerceServices->getCartManager()->getReservations($cart);
			if (count($reservations))
			{
				$unreserved = $commerceServices->getStockManager()->setReservations($cart->getIdentifier(), $reservations);
				if (count($unreserved))
				{
					$message = $i18nManager->trans('m.rbs.commerce.front.cart_reservation_error', array('ucf'));
					$err = new CartError($message);
					$errors[] = $err;
				}
			}
			else
			{
				$commerceServices->getStockManager()->unsetReservations($cart->getIdentifier());
			}
		}
	}
}