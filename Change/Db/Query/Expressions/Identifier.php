<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Identifier
 */
class Identifier extends AbstractExpression
{
	/**
	 * @var array
	 */
	protected $parts = array();
	
	/**
	 * @param array $parts
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
		return implode('.', array_map(function ($part) {
			return '"' . $part . '"';
		}, $this->parts));
	}
}