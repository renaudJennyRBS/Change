<?php
namespace Change\Transaction;

/**
 * @name \Change\Transaction\TransactionManager
 */
class TransactionManager extends \Exception
{
	/**
	 * @var \Change\Application 
	 */
	protected $application;
	
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
	public function __construct(\Change\Application $application)
	{
		$this->application = $application;	
	}
	
	/**
	 * @return boolean
	 */
	public function started()
	{
		return $this->count > 0;
	}
	
	/**
	 * @return boolean
	 */
	public function isDirty()
	{
		return $this->dirty;
	}
	
	/**
	 * @return integer
	 */
	public function count()
	{
		return $this->count;
	}
	
	/**
	 * @return \Change\Db\DbProvider
	 */
	protected function getDbProvider()
	{
		return $this->application->getApplicationServices()->getDbProvider();
	}
	
	public function begin()
	{
		$this->checkDirty();
		$this->count++;
		if ($this->count == 1)
		{
			$this->getDbProvider()->beginTransaction();
		}
		else
		{
			
		}	
	}
	
	public function commit()
	{
		$this->checkDirty();
		if ($this->count <= 0)
		{
			throw new \LogicException('Commit bad transaction count ('.$this->count.')');
		}	
		if ($this->count == 1)
		{
			$this->getDbProvider()->commit();
		}
		else
		{
			
		}
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
			throw new \LogicException('Rollback bad transaction count');
		}
		$this->count--;
		
		if (!$this->dirty)
		{
			$this->dirty = true;
		}
		if ($this->count == 0)
		{
			$this->dirty = false;
			$this->getDbProvider()->rollBack();
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
			throw new \LogicException('Transaction is dirty');
		}
	}
	
	public function __toString()
	{
		return 'Transaction count: ' .$this->count . ' dirty: ' .($this->dirty ? 'true' : 'false');
	}
}
