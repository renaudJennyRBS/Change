<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\AbstractExpression
 * @api
 */
abstract class AbstractExpression implements \Change\Db\Query\InterfaceSQLFragment
{
	/**
	 * SQL Specific vendor options.
	 *
	 * @api
	 * @var array
	 */
	protected $options;
	
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
}