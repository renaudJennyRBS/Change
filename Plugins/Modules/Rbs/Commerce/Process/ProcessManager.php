<?php
namespace Rbs\Commerce\Process;

/**
 * @name \Rbs\Commerce\Process\ProcessManager
 */
class ProcessManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'ProcessManager';

	/**
	 * @var \Rbs\Commerce\Cart\CartManager
	 */
	protected $cartManager;

	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging;

	/**
	 * @param \Rbs\Commerce\Cart\CartManager $cartManager
	 * @return $this
	 */
	public function setCartManager(\Rbs\Commerce\Cart\CartManager $cartManager)
	{
		$this->cartManager = $cartManager;
		return $this;
	}

	/**
	 * @return \Rbs\Commerce\Cart\CartManager
	 */
	protected function getCartManager()
	{
		return $this->cartManager;
	}

	/**
	 * @param \Change\Logging\Logging $logging
	 * @return $this
	 */
	public function setLogging(\Change\Logging\Logging $logging)
	{
		$this->logging = $logging;
		return $this;
	}

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		return $this->logging;
	}

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Commerce/Events/ProcessManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getNewTransaction', [$this, 'onDefaultGetNewTransaction'], 5);
		$eventManager->attach('createOrderFromCart', [$this, 'onDefaultCreateOrderFromCart'], 5);
	}

	/**
	 * @api
	 * @param string $targetIdentifier
	 * @param float $amount
	 * @param string $currencyCode
	 * @param string $email
	 * @param integer $userId
	 * @param integer $ownerId
	 * @param array $contextData
	 * @throws \Exception
	 * @return \Rbs\Payment\Documents\Transaction|null
	 */
	public function getNewTransaction($targetIdentifier, $amount, $currencyCode, $email, $userId, $ownerId, $contextData = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'targetIdentifier' => $targetIdentifier,
			'amount' => $amount,
			'currencyCode' => $currencyCode,
			'email' => $email,
			'userId' => $userId,
			'ownerId' => $ownerId,
			'contextData' => $contextData
		));
		$this->getEventManager()->trigger('getNewTransaction', $this, $args);
		if (isset($args['transaction']) && $args['transaction'] instanceof \Rbs\Payment\Documents\Transaction)
		{
			return $args['transaction'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetNewTransaction(\Change\Events\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			/** @var $transaction \Rbs\Payment\Documents\Transaction */
			$transaction = $dm->getNewDocumentInstanceByModelName('Rbs_Payment_Transaction');
			$transaction->setTargetIdentifier($event->getParam('targetIdentifier'));
			$transaction->setAmount($event->getParam('amount'));
			$transaction->setCurrencyCode($event->getParam('currencyCode'));
			$transaction->setEmail($event->getParam('email'));
			$transaction->setAuthorId($event->getParam('userId'));
			$transaction->setOwnerId($event->getParam('ownerId') ? $event->getParam('ownerId') : $event->getParam('userId'));
			$transaction->setContextData($event->getParam('contextData'));
			$transaction->save();

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}
		$event->setParam('transaction', $transaction);
	}

	/**
	 * @param $cart
	 * @return \Rbs\Order\Documents\Order|null
	 */
	public function createOrderFromCart($cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('cart' => $cart));
		$this->getEventManager()->trigger('createOrderFromCart', $this, $args);
		if (isset($args['order']) && $args['order'] instanceof \Rbs\Order\Documents\Order)
		{
			return $args['order'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultCreateOrderFromCart(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart)
		{
			$tm = $event->getApplicationServices()->getTransactionManager();
			try
			{
				$tm->begin();

				/* @var $order \Rbs\Order\Documents\Order */
				$order = $event->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Order_Order');
				$order->setEmail($cart->getEmail());
				$order->setAuthorId($cart->getUserId());
				$order->setOwnerId($cart->getOwnerId() ? $cart->getOwnerId() : $cart->getUserId());
				$order->setWebStoreId($cart->getWebStoreId());
				$order->setBillingAreaId($cart->getBillingArea()->getId());
				$order->setContext($cart->getContext()->toArray());
				foreach ($cart->getLines() as $line)
				{
					$order->appendLine($line->toArray());
				}
				$order->setAddress($cart->getAddress()->toArray());
				$order->setShippingModes($cart->getShippingModes());
				// TODO: fees, discounts, coupons...
				$order->setPaymentAmount($cart->getPaymentAmount());
				$order->setCurrencyCode($cart->getCurrencyCode());
				$order->setProcessingStatus(\Rbs\Order\Documents\Order::PROCESSING_STATUS_PROCESSING);
				$order->save();

				$this->getCartManager()->affectOrder($cart, $order);

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$event->setParam('order', $order);
		}
	}
}