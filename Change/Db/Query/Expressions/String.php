<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Raw
 */
class String extends \Change\Db\Query\Expressions\Value
{
	
	/**
	 * @param string $string
	 */
	public function __construct($string = null)
	{
		parent::__construct($string, \Change\Db\ScalarType::STRING);
	}
}