<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Numeric
 */
class Numeric extends \Change\Db\Query\Expressions\Value
{
	/**
	 * @param integer|float $value
	 */
	public function __construct($value = null)
	{
		parent::__construct($value, \Change\Db\ScalarType::DECIMAL);
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		if ($this->value === null)
		{
			return 'NULL';
		}
		return strval(floatval($this->value));
	}
}