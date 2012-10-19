<?php
namespace Change\Db\Schema;

/** 
 * @name \Change\Db\Schema\FieldDefinition
 * 
 */
class FieldDefinition
{
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var string
	 */
	protected $type;
	
	/**
	 * @var string
	 */
	protected $typeData;
	
	/**
	 * @var string
	 */
	protected $defaultValue;

	/**
	 * @var boolean
	 */
	protected $nullable;
	
	/**
	 * @param string $name
	 * @param string $type
	 * @param string $typeData
	 * @param boolean $nullable
	 * @param string $defaultValue
	 */
	public function __construct($name, $type, $typeData = null, $nullable = true, $defaultValue = null)
	{
		$this->name = $name;
		$this->type = $type;
		$this->typeData = $typeData;
		$this->nullable = $nullable;
		$this->defaultValue = $defaultValue;
	}
	
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getTypeData()
	{
		return $this->typeData;
	}

	/**
	 * @return string
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @return boolean
	 */
	public function getNullable()
	{
		return $this->nullable;
	}

	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param string $type
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @param string $typeData
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setTypeData($typeData)
	{
		$this->typeData = $typeData;
		return $this;
	}

	/**
	 * @param string $defaultValue
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
		return $this;
	}

	/**
	 * @param boolean $nullable
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setNullable($nullable)
	{
		$this->nullable = ($nullable == true);
		return $this;
	}
	
}