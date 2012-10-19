<?php
namespace Change\Db\Schema;

/** 
 * @name \Change\Db\Schema\TableDefinition
 * 
 */
class TableDefinition
{
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var \Change\Db\Schema\FieldDefinition[]
	 */
	protected $fields = array();
	
	/**
	 * @var \Change\Db\Schema\KeyDefinition[]
	 */
	protected $keys = array();
	
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return \Change\Db\Schema\FieldDefinition[]
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @return \Change\Db\Schema\KeyDefinition[]
	 */
	public function getKeys()
	{
		return $this->keys;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param \Change\Db\Schema\FieldDefinition[] $fields
	 */
	public function setFields($fields)
	{
		$this->fields = array();
		foreach ($fields as $field)
		{
			if ($field instanceof FieldDefinition)
			{
				$this->fields[$field->getName()] = $field;
			}
		}
	}

	/**
	 * @param \Change\Db\Schema\KeyDefinition[] $keys
	 */
	public function setKeys($keys)
	{
		$this->keys = array();
		foreach ($keys as $key)
		{
			if ($key instanceof KeyDefinition)
			{
				$this->keys[] = $key;
			}
		}
	}
	
	/**
	 * @param \Change\Db\Schema\FieldDefinition $field
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function addField(\Change\Db\Schema\FieldDefinition $field)
	{
		$this->fields[$field->getName()] = $field;
		return $this;
	}	

	/**
	 * @param \Change\Db\Schema\KeyDefinition $key
	 * @return \Change\Db\Schema\TableDefinition
	 */
	public function addKey(\Change\Db\Schema\KeyDefinition $key)
	{
		$this->keys[] = $key;
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isValid()
	{
		return $this->name !== null && count($this->fields) > 0;
	}
	
	/**
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition|NULL
	 */
	public function getField($name)
	{
		return isset($this->fields[$name]) ? $this->fields[$name] : null;
	}
}