<?php
namespace Change\Db\Query\Expressions;

/**
 * @name \Change\Db\Query\Expressions\Parameter
 */
class Parameter extends AbstractExpression
{	
	const STRING = 1;
	const NUMERIC = 2;
	const DATETIME = 4;
	const LOB = 8;
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var integer
	 */
	protected $type;
	
	/**
	 * @param string $parameter
	 * @param integer $type
	 */
	public function __construct($name, $type = self::STRING)
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
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @param integer $type
	 */
	public function setType($type)
	{
		switch ($type) 
		{
			case static::STRING:
			case static::NUMERIC:
			case static::DATETIME:
			case static::LOB:
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