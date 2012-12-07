<?php

namespace Change\Db\Query\Expressions;

class Numeric extends AbstractExpression
{
	protected $value;
	
	public function __construct($value = null)
	{
		$this->value = $value;
	}
	
	/**
	 * @return field_type
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param field_type $value
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