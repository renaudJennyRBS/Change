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
		$this->setParts($parts);
	}
	
	/**
	 * @return string[]
	 */
	public function getParts()
	{
		return $this->parts;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @param string[] $parts
	 */
	public function setParts($parts)
	{
		if (!is_array($parts))
		{
			throw new \InvalidArgumentException('Argument 1 must be a Array');
		}
		$this->parts = array();
		foreach ($parts as $value)
		{
			$part = trim(strval($value));
			if ($part !== '')
			{
				$this->parts[] = $part;
			}
		}
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