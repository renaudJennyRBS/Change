<?php
namespace Change\Mvc;

/**
 * @name \Change\Mvc\ActionStack
 */
class ActionStack
{
	/**
	 * @var array
	 */
	private $stack = array();

	/**
	 * @param \Change\Mvc\AbstractAction $action
	 */
	public function addEntry($action)
	{
		$this->stack[] = $action;
	}

	/**
	 * @param integer $index
	 * @return \Change\Mvc\AbstractAction|null
	 */
	public function getEntry($index)
	{
		$retval = null;
		if ($index > -1 && $index < count($this->stack))
		{
			$retval = $this->stack[$index];
		}
		return $retval;
	}

	/**
	 * @return \Change\Mvc\AbstractAction|null
	 */
	public function getFirstEntry()
	{
		$count  = count($this->stack);
		$retval = null;
		if ($count > 0)
		{
			$retval = $this->stack[0];
		}
		return $retval;
	}

	/**
	 * @return \Change\Mvc\AbstractAction|null
	 */
	public function getLastEntry()
	{
		$count  = count($this->stack);
		$retval = null;
		if ($count > 0)
		{
			$retval = $this->stack[$count - 1];
		}
		return $retval;
	}

	/**
	 * @return integer
	 */
	public function getSize()
	{
		return count($this->stack);
	}
	
	/**
	 * @return \Change\Mvc\AbstractAction|null
	 */
	public function popEntry()
	{
		$count  = count($this->stack);
		$retval = null;
		if ($count > 0)
		{
			$retval = array_pop($this->stack);
		}
		return $retval;
	}
}