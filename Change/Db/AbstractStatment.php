<?php
namespace Change\Db;

/**
 * @name \Change\Db\AbstractStatment
 */
abstract class AbstractStatment
{
	const FETCH_ASSOC = 'FETCH_ASSOC';
	const FETCH_NUM = 'FETCH_NUM';
	const FETCH_COLUMN = 'FETCH_COL';
	
	/**
	 * @var string
	 */
	protected $sql;
	
	/**
	 * @param string $sql
	 * @param \Change\Db\StatmentParameter[] $parameters
	 */
	public function __construct($sql, $parameters = null)
	{
		$this->sql = $sql;
		if (is_array($parameters))
		{
			foreach ($parameters as $parameter)
			{
				if ($parameter instanceof StatmentParameter)
				{
					$this->addParameter($parameter);
				}
			}
		}
	}
	
	/**
	 * @return string
	 */
	public function getSql()
	{
		return $this->sql;
	}
	
	/**
	 * @param \Change\Db\StatmentParameter $parameter
	 * @return \Change\Db\AbstractStatment
	 */
	abstract public function addParameter(\Change\Db\StatmentParameter $parameter);
	
	/**
	 * @param \Change\Db\StatmentParameter[] $parameters
	 * @return boolean
	 */
	abstract public function execute($parameters = null);
	
	/**
	 * @return string|null
	 */
	abstract public function getErrorMessage();
	
	/**
	 * @return integer
	 */
	abstract public function rowCount();
	
	/**
	 * @param string $mode \Change\Db\AbstractStatment::FETCH_*
	 * @return array|false
	 */
	abstract public function fetch($mode);
	
	/**
	 * @param string $mode \Change\Db\AbstractStatment::FETCH_*
	 * @return array
	 */
	abstract public function fetchAll($mode);
	
	/**
	 * @return void
	 */
	abstract public function close();
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getSql();
	}
}