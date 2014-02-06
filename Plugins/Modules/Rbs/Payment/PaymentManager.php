<?php
namespace Rbs\Payment;

/**
 * @name \Rbs\Payment\PaymentManager
 */
class PaymentManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'PaymentManager';

	/**
	 * @var \Change\Transaction\TransactionManager
	 */
	protected $transactionManager;

	/**
	 * @var \Change\Documents\DocumentManager
	 */
	protected $documentManager;

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return $this
	 */
	public function setDocumentManager($documentManager)
	{
		$this->documentManager = $documentManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\DocumentManager
	 */
	protected function getDocumentManager()
	{
		return $this->documentManager;
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @return $this
	 */
	public function setTransactionManager($transactionManager)
	{
		$this->transactionManager = $transactionManager;
		return $this;
	}

	/**
	 * @return \Change\Transaction\TransactionManager
	 */
	protected function getTransactionManager()
	{
		return $this->transactionManager;
	}


	/**
	 * @return null|string|string[]
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
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Payment/Events/PaymentManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('getMailCode', array($this, 'onDefaultGetMailCode'), 5);
		$eventManager->attach('getMailSubstitutions', array($this, 'onDefaultGetMailSubstitutions'), 5);
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return string|null
	 */
	public function getMailCode($transaction)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'transaction' => $transaction
		));
		$eventManager->trigger('getMailCode', $this, $args);
		return isset($args['code']) ? $args['code'] : null;
	}

	/**
	 * @param \Rbs\Payment\Documents\Transaction $transaction
	 * @return array
	 */
	public function getMailSubstitutions($transaction)
	{
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(array(
			'transaction' => $transaction
		));
		$eventManager->trigger('getMailSubstitutions', $this, $args);
		return isset($args['substitutions']) ? $args['substitutions'] : [];
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 * @return array
	 */
	public function onDefaultGetMailCode($event)
	{
		$transaction = $event->getParam('transaction');
		if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
		{
			switch ($transaction->getProcessingStatus())
			{
				case \Rbs\Payment\Documents\Transaction::STATUS_PROCESSING:
					$event->setParam('code', 'rbs_payment_transaction_processing');
					break;
				case \Rbs\Payment\Documents\Transaction::STATUS_SUCCESS:
					$event->setParam('code', 'rbs_payment_transaction_success');
					break;
				case \Rbs\Payment\Documents\Transaction::STATUS_FAILED:
					$event->setParam('code', 'rbs_payment_transaction_failed');
					break;
			}
		}
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 * @return array
	 */
	public function onDefaultGetMailSubstitutions($event)
	{
		//TODO
		$event->setParam('substitutions', []);
	}
}