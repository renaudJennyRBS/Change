<?php
namespace Change\Db;

/**
 * @name \Change\Db\StatmentParameter
 */
class StatmentParameter
{
	const NIL = 'NIL';
	const INT = 'INT';
	const DATE = 'DATE';
	const STR = 'STR';
	const LOB = 'LOB';
	const FLOAT = 'FLOAT';
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * @var string
	 */
	protected $type;

	
	public function __construct($name, $value = null, $type = self::STR)
	{
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
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
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}
}