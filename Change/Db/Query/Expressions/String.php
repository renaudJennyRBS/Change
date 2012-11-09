<?php

namespace Change\Db\Query\Expressions;

class String extends AbstractExpression
{
	/**
	 * @var string
	 */
	protected $string;
	
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
	 */
	public function toSQL92String()
	{
		return "'" . $this->string . "'";
	}
}