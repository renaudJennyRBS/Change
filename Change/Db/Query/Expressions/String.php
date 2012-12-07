<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Raw
 */
class String extends AbstractExpression
{
	/**
	 * @var string
	 */
	protected $string;
	
	/**
	 * @param unknown_type $string
	 */
	public function __construct($string = null)
	{
		$this->string = $string;
	}
	
	/**
	 * @return string
	 */
	public function getString()
	{
		return $this->string;
	}
	
	/**
	 * @param string $string
	 */
	public function setString($string)
	{
		$this->string = $string;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return "'" . $this->string . "'";
	}
}