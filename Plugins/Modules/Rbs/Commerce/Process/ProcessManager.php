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
		$eventManager->attach('getOrderProcessByCart', [$this, 'onDefaultGetOrderProcessByCart'], 5);
		$eventManager->attach('getCompatibleShippingModes', [$this, 'onDefaultGetCompatibleShippingModes'], 5);
		$eventManager->attach('getCompatiblePaymentConnectors', [$this, 'onDefaultGetCompatiblePaymentConnectors'], 5);
		$eventManager->attach('getShippingFee', [$this, 'onDefaultGetShippingFee'], 5);
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Commerce\Documents\Process|null
	 */
	public function getOrderProcessByCart($cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['cart' => $cart]);
		$this->getEventManager()->trigger('getOrderProcessByCart', $this, $args);
		if (isset($args['process']) && $args['process'] instanceof \Rbs\Commerce\Documents\Process)
		{
			return $args['process'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetOrderProcessByCart(\Change\Events\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$cart = $event->getParam('cart');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart) {
			$webstore = $dm->getDocumentInstance($cart->getWebStoreId());
			if ($webstore instanceof \Rbs\Store\Documents\WebStore) {
				$process = $webstore->getOrderProcess();
				if ($process && $process->activated()) {
					$event->setParam('process', $process);
				}
			}
		}
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Shipping\Documents\Mode[]
	 */
	public function getCompatibleShippingModes($orderProcess, $cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart]);
		$this->getEventManager()->trigger('getCompatibleShippingModes', $this, $args);
		if (isset($args['shippingModes']) && is_array($args['shippingModes']))
		{
			return $args['shippingModes'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCompatibleShippingModes(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$shippingModes = [];
			foreach ($orderProcess->getShippingModes() as $shippingMode)
			{
				if ($shippingMode->isCompatibleWith($cart))
				{
					$shippingModes[] = $shippingMode;
				}
			}
			$event->setParam('shippingModes', $shippingModes);
		}
	}


	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @return \Rbs\Payment\Documents\Connector[]
	 */
	public function getCompatiblePaymentConnectors($orderProcess, $cart)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart]);
		$this->getEventManager()->trigger('getCompatiblePaymentConnectors', $this, $args);
		if (isset($args['paymentConnectors']) && is_array($args['paymentConnectors']))
		{
			return $args['paymentConnectors'];
		}
		return [];
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCompatiblePaymentConnectors(\Change\Events\Event $event)
	{
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart && $orderProcess instanceof \Rbs\Commerce\Documents\Process)
		{
			$paymentConnectors = [];
			foreach ($orderProcess->getPaymentConnectors() as $paymentConnector)
			{
				if ($paymentConnector->isCompatibleWith($cart))
				{
					$paymentConnectors[] = $paymentConnector;
				}
			}
			$event->setParam('paymentConnectors', $paymentConnectors);
		}
	}

	/**
	 * @api
	 * @param \Rbs\Commerce\Documents\Process $orderProcess
	 * @param \Rbs\Commerce\Cart\Cart $cart
	 * @param \Rbs\Shipping\Documents\Mode $shippingMode
	 * @return \Rbs\Commerce\Documents\Fee|null
	 */
	public function getShippingFee($orderProcess, $cart, $shippingMode)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(['orderProcess' => $orderProcess, 'cart' => $cart, 'shippingMode' => $shippingMode]);
		$this->getEventManager()->trigger('getShippingFee', $this, $args);
		if (isset($args['fee']) && $args['fee'] instanceof \Rbs\Commerce\Documents\Fee)
		{
			return $args['fee'];
		}
		return null;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultGetShippingFee(\Change\Events\Event $event)
	{
		$dm = $event->getApplicationServices()->getDocumentManager();
		$cart = $event->getParam('cart');
		$orderProcess = $event->getParam('orderProcess');
		$shippingMode = $event->getParam('shippingMode');
		if ($cart instanceof \Rbs\Commerce\Cart\Cart &&
			$orderProcess instanceof \Rbs\Commerce\Documents\Process &&
			$shippingMode instanceof \Rbs\Shipping\Documents\Mode)
		{
			$q = $dm->getNewQuery('Rbs_Commerce_Fee');
			$q->andPredicates($q->activated(), $q->eq('orderProcess', $orderProcess), $q->eq('shippingMode', $shippingMode));
			/** @var $fee \Rbs\Commerce\Documents\Fee */
			foreach ($q->getDocuments() as $fee)
			{
				if ($fee->getValidModifier($cart, ['shippingMode' => $shippingMode]))
				{
					$event->setParam('fee', $fee);
					return;
				}
			}
		}
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
	 * @param \Rbs\Commerce\Cart\Cart $cart
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
				$order->setContext($cart->getContext()->toArray());
				$order->setCreationDate($cart->lastUpdate());
				$order->setAuthorId($cart->getUserId());
				$order->setEmail($cart->getEmail());

				$order->setOwnerId($cart->getOwnerId() ? $cart->getOwnerId() : $cart->getUserId());
				$order->setWebStoreId($cart->getWebStoreId());
				$order->setBillingAreaId($cart->getBillingArea()->getId());
				$order->setCurrencyCode($cart->getCurrencyCode());
				$order->setZone($cart->getZone());
				$order->setTaxes($cart->getTaxes());

				foreach ($cart->getLines() as $line)
				{
					$order->appendLine($line->toArray());
				}
				$order->setLinesAmount($cart->getLinesAmount());
				$order->setLinesTaxes($cart->getLinesTaxes());
				$order->setLinesAmountWithTaxes($cart->getLinesAmountWithTaxes());

				$order->setAddress($cart->getAddress()->toArray());
				$order->setShippingModes($cart->getShippingModes());

				$order->setCoupons($cart->getCoupons());
				$order->setFees($cart->getFees());
				$order->setDiscounts($cart->getDiscounts());

				$order->setTotalAmount($cart->getTotalAmount());
				$order->setTotalTaxes($cart->getTotalTaxes());
				$order->setTotalAmountWithTaxes($cart->getTotalAmountWithTaxes());

				$order->setCreditNotes($cart->getCreditNotes());

				$order->setPaymentAmountWithTaxes($cart->getPaymentAmountWithTaxes());

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