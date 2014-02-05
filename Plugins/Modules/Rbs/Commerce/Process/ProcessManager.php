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
			$cart = $this->getCartManager()->getCartByIdentifier($transaction->getTargetIdentifier());
			if ($cart instanceof \Rbs\Commerce\Cart\Cart)
			{
				$this->getCartManager()->affectUser($cart, $user);
			}

			// TODO affect the order if the cart is already converted.
		}
	}
}