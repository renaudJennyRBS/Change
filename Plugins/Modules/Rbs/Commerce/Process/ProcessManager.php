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
		$eventManager->attach('handleRegistrationForTransaction', [$this, 'onDefaultHandleRegistrationForTransaction'], 5);
		$eventManager->attach('handleProcessingForTransaction', [$this, 'onDefaultHandleProcessingForTransaction'], 5);
		$eventManager->attach('handleSuccessForTransaction', [$this, 'onDefaultHandleSuccessForTransaction'], 5);
		$eventManager->attach('handleFailedForTransaction', [$this, 'onDefaultHandleFailedForTransaction'], 5);
	}

	/**
	 * @api
	 * @param $targetIdentifier
	 * @param $amount
	 * @param $currencyCode
	 * @param array $contextData
	 * @throws \Exception
	 * @return \Rbs\Payment\Documents\Transaction|null
	 */
	public function getNewTransaction($targetIdentifier, $amount, $currencyCode, $contextData = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array(
			'targetIdentifier' => $targetIdentifier,
			'amount' => $amount,
			'currencyCode' => $currencyCode,
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
	 * @api
	 * @param \Rbs\User\Documents\User $user
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 */
	public function handleRegistrationForTransaction($user, $transaction)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('user' => $user, 'transaction' => $transaction));
		$this->getEventManager()->trigger('handleRegistrationForTransaction', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultHandleRegistrationForTransaction(\Change\Events\Event $event)
	{
		$user = $event->getParam('user');
		$transaction = $event->getParam('transaction');

		if ($user instanceof \Rbs\User\Documents\User && $transaction instanceof \Rbs\Payment\Documents\Transaction)
		{
			if (isset($contextData['from']) && $contextData['from'] == 'cart')
			{
				/* @var $commerceServices \Rbs\Commerce\CommerceServices */
				$commerceServices = $event->getServices('commerceServices');
				$cartManager = $commerceServices->getCartManager();
				$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
				if ($cart instanceof \Rbs\Commerce\Cart\Cart)
				{
					$cartManager->affectUser($cart, $user);
				}

				// TODO affect the order if the cart is already converted.
			}
		}
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 */
	public function handleProcessingForTransaction($transaction)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('transaction' => $transaction));
		$this->getEventManager()->trigger('handleProcessingForTransaction', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultHandleProcessingForTransaction(\Change\Events\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $event->getParam('transaction');
		$contextData = $transaction->getContextData();

		// Update the cart.
		if (isset($contextData['from']) && $contextData['from'] == 'cart')
		{
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				// Set cart as processing.
				if (!$cart->isProcessing())
				{
					$cartManager->startProcessingCart($cart);
				}

				// Remove cart from context.
				$context = $commerceServices->getContext();
				if ($context->getCartIdentifier() == $transaction->getTargetIdentifier())
				{
					$context->setCartIdentifier(null)->save();
				}
			}
		}

		// Send the email notification.
		$connector = $transaction->getConnector();
		$email = isset($contextData['email']) ? $contextData['email'] : null;
		$websiteId = isset($contextData['websiteId']) ? $contextData['websiteId'] : null;
		$LCID = isset($contextData['LCID']) ? $contextData['LCID'] : null;
		if ($email && $websiteId && $LCID && $connector->getProcessingMail())
		{
			/* @var $website \Rbs\Website\Documents\Website */
			$website = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($websiteId);
			if ($website instanceof \Rbs\Website\Documents\Website)
			{
				$paymentManager = $commerceServices->getPaymentManager();
				$genericServices = $event->getServices('genericServices');
				if (!($genericServices instanceof \Rbs\Generic\GenericServices))
				{
					throw new \RuntimeException('Unable to get CommerceServices', 999999);
				}
				$mailManager = $genericServices->getMailManager();
				$code = $paymentManager->getMailCode($transaction);
				$substitutions = $paymentManager->getMailSubstitutions($transaction);
				$mailManager->send($code, $website, $LCID, [$email], $substitutions);
			}
		}
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 */
	public function handleSuccessForTransaction($transaction)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('transaction' => $transaction));
		$this->getEventManager()->trigger('handleSuccessForTransaction', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultHandleSuccessForTransaction(\Change\Events\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $event->getParam('transaction');
		$contextData = $transaction->getContextData();

		// Update the cart.
		if (isset($contextData['from']) && $contextData['from'] == 'cart')
		{
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				// Set transactionId in cart.
				$cartManager->affectTransactionId($cart, $transaction->getId());
			}
		}

		// Send the email notification.
		// TODO
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 */
	public function handleFailedForTransaction($transaction)
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('transaction' => $transaction));
		$this->getEventManager()->trigger('handleSuccessForTransaction', $this, $args);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultHandleFailedForTransaction(\Change\Events\Event $event)
	{
		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');

		/* @var $transaction \Rbs\Payment\Documents\Transaction */
		$transaction = $event->getParam('transaction');
		$contextData = $transaction->getContextData();

		// Update the cart.
		if (isset($contextData['from']) && $contextData['from'] == 'cart')
		{
			$cartManager = $commerceServices->getCartManager();
			$cart = $cartManager->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				// TODO: is there anything to do here?
			}
		}

		// Send the email notification.
		// TODO
	}
}