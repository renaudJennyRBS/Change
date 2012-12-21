<?php
namespace Change\Db\Query\Clauses;

/**
 * @name \Change\Db\Query\Clauses\AbstractClause
 */
abstract class AbstractClause implements \Change\Db\Query\InterfaceSQLFragment
{
	/**
	 * SQL Specific vendor options.
	 * 
	 * @api 
	 * @var array
	 */
	protected $options;
	
	/**
	 * @api
	 * @var string
	 */
	protected $name;
	
	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}	
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

}