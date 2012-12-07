<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Raw
 */
class Raw extends AbstractExpression
{
	/**
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * @param mixed $value
	 */
	public function __construct($value = null)
	{
		$this->value = $value;
	}
	
	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	/**
	 * @param mixed $value
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