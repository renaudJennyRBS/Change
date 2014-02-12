<?php
namespace Rbs\Commerce\Http\Web;

/**
 * @name \Rbs\Commerce\Http\Web\GetTransaction
 */
class GetTransaction extends \Change\Http\Web\Actions\AbstractAjaxAction
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
			$processManager = $commerceServices->getProcessManager();
			$cartIdentifier = $commerceServices->getContext()->getCartIdentifier();
			$cart = ($cartIdentifier) ? $cartManager->getCartByIdentifier($cartIdentifier) : null;
			if ($cart)
			{
				if (!$cart->isLocked())
				{
					$cartManager->lockCart($cart);
				}

				$contextData = $cart->getContext()->toArray();
				$contextData['from'] = 'cart';
				$contextData['guestCheckout'] = !$cart->getOwnerId() && !$cart->getUserId();
				$contextData['websiteId'] = $event->getWebsite()->getId();
				$contextData['LCID'] = $event->getApplicationServices()->getDocumentManager()->getLCID();
				$contextData['returnSuccessFunction'] = 'Rbs_Commerce_PaymentReturn';
				$transaction = $processManager->getNewTransaction(
					$cart->getIdentifier(),
					$cart->getPriceValueWithTax(),
					$cart->getCurrencyCode(),
					$cart->getEmail(),
					$cart->getUserId(),
					$cart->getOwnerId(),
					$contextData
				);

				$data = array(
					'id' => $transaction->getId(),
					'targetIdentifier' => $transaction->getTargetIdentifier(),
					'contextData' => $transaction->getContextData(),
					'amount' => $transaction->getAmount(),
					'currencyCode' => $transaction->getCurrencyCode()
				);
				$result = $this->getNewAjaxResult($data);
				$event->setResult($result);
				return;
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