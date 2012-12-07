<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Numeric
 */
class Numeric extends AbstractExpression
{
	/**
	 * @var number
	 */
	protected $value;
	
	/**
	 * @param number $value
	 */
	public function __construct($value = null)
	{
		$this->value = $value;
	}
	
	/**
	 * @return number
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param number $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return strval($this->value);
	}
}