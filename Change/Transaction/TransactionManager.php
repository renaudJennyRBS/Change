<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Transaction;

/**
 * @name \Change\Transaction\TransactionManager
 */
class TransactionManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;
	
	const EVENT_MANAGER_IDENTIFIER = 'TransactionManager';

	const EVENT_BEGIN = 'begin';
	const EVENT_COMMIT = 'commit';
	const EVENT_ROLLBACK = 'rollback';

	/**
	 * @var integer
	 */
	protected $count = 0;

	/**
	 * @var boolean
	 */
	protected $dirty = false;

	/**
	 * @return string
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return array
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Change/Events/TransactionManager');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		(new DefaultListeners())->attach($eventManager);
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

		$event = new \Change\Events\Event(static::EVENT_BEGIN, $this, $args);
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

		$event = new \Change\Events\Event(static::EVENT_COMMIT, $this, $args);
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
		$event = new \Change\Events\Event(static::EVENT_ROLLBACK, $this, $args);
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
