<?php

namespace Change\Db\Query\Expressions;

class Identifier extends AbstractExpression
{
	/**
	 * @var array
	 */
	protected $parts = array();
	
	/**
	 * @param \Change\Db\Query\Objects\Table $table
	 * @param string $columnName
	 */
	public function __construct($parts = array())
	{
		$this->parts = $parts;
	}
	
	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return implode('.', array_map(function($part){
			return '"' . $part . '"';
		}, $this->parts));
	}
}