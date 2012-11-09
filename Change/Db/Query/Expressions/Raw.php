<?php

namespace Change\Db\Query\Expressions;

class Raw extends AbstractExpression
{
	/**
	 * @var mixed
	 */
	protected $value;
	
	public function __construct($string = null)
	{
		$this->value = $string;
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
	 */
	public function toSQL92String()
	{
		return strval($this->value);
	}
}