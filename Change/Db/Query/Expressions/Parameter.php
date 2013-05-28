<?php
namespace Change\Db\Query\Expressions;

use Change\Db\ScalarType;

/**
 * @name \Change\Db\Query\Expressions\Parameter
 */
class Parameter extends AbstractExpression
{		
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var integer as \Change\Db\ScalarType::*
	 */
	protected $type;
	
	/**
	 * @param string $name
	 * @param integer $type
	 */
	public function __construct($name, $type = ScalarType::STRING)
	{
		$this->setName($name);
		$this->setType($type);
	}
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return integer as \Change\Db\ScalarType::*
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @param integer $type as \Change\Db\ScalarType::*
	 */
	public function setType($type)
	{
		switch ($type) 
		{
			case ScalarType::STRING:
			case ScalarType::BOOLEAN:
			case ScalarType::DATETIME:
			case ScalarType::DECIMAL:
			case ScalarType::INTEGER:
			case ScalarType::LOB:
			case ScalarType::TEXT:
				$this->type = $type;
				return;
		}
		throw new \InvalidArgumentException('Argument 1 must be a valid const', 42027);
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return ':' . $this->getName();
	}
}