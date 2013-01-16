<?php
namespace Change\Db\Query\Expressions;

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
	 * @var integer \Change\Db\ScalarType::*
	 */
	protected $type;
	
	/**
	 * @param string $parameter
	 * @param integer $type
	 */
	public function __construct($name, $type = \Change\Db\ScalarType::STRING)
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
	 * @param string $parameter
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @return integer \Change\Db\ScalarType::*
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @param integer $type \Change\Db\ScalarType::*
	 */
	public function setType($type)
	{
		switch ($type) 
		{
			case \Change\Db\ScalarType::STRING:
			case \Change\Db\ScalarType::BOOLEAN:
			case \Change\Db\ScalarType::DATETIME:
			case \Change\Db\ScalarType::DECIMAL:
			case \Change\Db\ScalarType::INTEGER:
			case \Change\Db\ScalarType::LOB:
			case \Change\Db\ScalarType::TEXT:
				$this->type = $type;
				return;
		}
		throw new \InvalidArgumentException('Argument 1 must be a valid const');
	}

	/**
	 * @return string
	 */
	public function toSQL92String()
	{
		return ':' . $this->getName();
	}
}