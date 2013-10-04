<?php
namespace Change\Transaction;

/**
 * @name \Change\Transaction\TransactionManager
 */
class TransactionManager extends \Exception
{
	const EVENT_MANAGER_IDENTIFIER = 'TransactionManager';

	const EVENT_BEGIN = 'begin';
	const EVENT_COMMIT = 'commit';
	const EVENT_ROLLBACK = 'rollback';

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Zend\EventManager\EventManager
	 */
	protected $eventManager;

	/**
	 * @var integer
	 */
	protected $count = 0;

	/**
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 * @param \Change\Application $application
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		return $this->application->getSharedEventManager();
	}

	/**
	 * @return \Zend\EventManager\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = new \Zend\EventManager\EventManager(static::EVENT_MANAGER_IDENTIFIER);
			$this->eventManager->setSharedManager($this->getSharedEventManager());
		}
		return $this->eventManager;
	}

	/**
	 * @return boolean
	 */
	public function started()
	{
		return $this->count > 0;
	}

	/**
	 * @return integer
	 */
	public function count()
	{
		return $this->count;
	}

	public function begin()
	{
		$this->checkDirty();
		$this->count++;

		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('primary' => $this->count === 1, 'count' => $this->count));

		$event = new \Zend\EventManager\Event(static::EVENT_BEGIN, $this, $args);
		$em->trigger($event);
	}

	public function commit()
	{
		$this->checkDirty();
		if ($this->count <= 0)
		{
			throw new \LogicException('Commit bad transaction count (' . $this->count . ')', 121000);
		}

		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('primary' => $this->count === 1, 'count' => $this->count));

		$event = new \Zend\EventManager\Event(static::EVENT_COMMIT, $this, $args);
		$em->trigger($event);

		$this->count--;
	}

	/**
	 * @param \Exception $e
	 * @throws \LogicException
	 * @throws \Change\Transaction\RollbackException
	 * @return \Exception
	 */
	public function rollBack(\Exception $e = null)
	{
		if ($this->count == 0)
		{
			throw new \LogicException('Rollback bad transaction count', 121001);
		}

		$this->dirty = true;

		$em = $this->getEventManager();
		$args = $em->prepareArgs(array('primary' => $this->count === 1, 'count' => $this->count));
		$event = new \Zend\EventManager\Event(static::EVENT_ROLLBACK, $this, $args);
		$em->trigger($event);

		$this->count--;

		if ($this->count === 0)
		{
			$this->dirty = false;
		}
		else
		{
			if (!($e instanceof RollbackException))
			{
				$e = new RollbackException($e);
			}
			throw $e;
		}

		return ($e instanceof RollbackException) ? $e->getPrevious() : $e;
	}

	/**
	 * @throws \LogicException
	 */
	protected final function checkDirty()
	{
		if ($this->dirty)
		{
			throw new \LogicException('Transaction is dirty', 121002);
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return 'Transaction count: ' . $this->count . ' dirty: ' . ($this->dirty ? 'true' : 'false');
	}
}
