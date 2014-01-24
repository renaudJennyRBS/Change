<?php
namespace Rbs\Commerce\Events\CartManager;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\CartManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$identifier = $event->getParam('identifier');
				$cart = $cartStorage->getNewCart($identifier);
				if ($cart)
				{
					$cart->setCartManager($cs->getCartManager());
					$cart->setDocumentManager($event->getApplicationServices()->getDocumentManager());
					$event->setParam('cart', $cart);
				}
			}
		};
		$events->attach('getNewCart', $callback, 5);

		$callback = function (Event $event)
		{
			$cs = $event->getServices('commerceServices');
			if ($cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$cart = $cartStorage->loadCart($event->getParam('cartIdentifier'));
				if ($cart)
				{
					$cart->setCartManager($cs->getCartManager());
					$cart->setDocumentManager($event->getApplicationServices()->getDocumentManager());
					$event->setParam('cart', $cart);
				}
			}
		};
		$events->attach('getCartByIdentifier', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$cs = $event->getServices('commerceServices');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$cartStorage->saveCart($cart);
			}
		};
		$events->attach('saveCart', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$cartToMerge = $event->getParam('cartToMerge');
			$cs = $event->getServices('commerceServices');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cs instanceof \Rbs\Commerce\CommerceServices
				&& $cartToMerge instanceof \Rbs\Commerce\Cart\Cart
			)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$event->setParam('cart', $cartStorage->mergeCart($cart, $cartToMerge));
			}
		};
		$events->attach('mergeCart', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$newCart = $event->getParam('newCart');
			$cs = $event->getServices('commerceServices');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cs instanceof \Rbs\Commerce\CommerceServices
				&& $newCart instanceof \Rbs\Commerce\Cart\Cart
			)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$event->setParam('cart', $cartStorage->getUnlockedCart($cart, $newCart));
			}
		};
		$events->attach('getUnlockedCart', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$cs = $event->getServices('commerceServices');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cs instanceof \Rbs\Commerce\CommerceServices)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$cartStorage->lockCart($cart);
			}
		};
		$events->attach('lockCart', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$transactionId = $event->getParam('transactionId');
			$cs = $event->getServices('commerceServices');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cs instanceof \Rbs\Commerce\CommerceServices
				&& is_numeric($transactionId)
			)
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$cartStorage->affectTransactionId($cart, $transactionId);
			}
		};
		$events->attach('affectTransactionId', $callback, 5);

		$callback = function (Event $event)
		{
			$cart = $event->getParam('cart');
			$order = $event->getParam('order');
			$cs = $event->getServices('commerceServices');
			if ($cart instanceof \Rbs\Commerce\Cart\Cart && $cs instanceof \Rbs\Commerce\CommerceServices && isset($order))
			{
				$as = $event->getApplicationServices();
				$cartStorage = new \Rbs\Commerce\Cart\CartStorage();
				$cartStorage->setTransactionManager($as->getTransactionManager())
					->setDbProvider($as->getDbProvider())
					->setDocumentManager($as->getDocumentManager())
					->setContext($cs->getContext());
				$cartStorage->affectOrder($cart, $order);
			}
		};
		$events->attach('affectOrder', $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}