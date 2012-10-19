<?php
namespace Change\Db\Schema;

/** 
 * @name \Change\Db\Schema\KeyDefinition
 * 
 */
class KeyDefinition
{
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var boolean
	 */
	protected $primary;	
	
	/**
	 * @var \Change\Db\Schema\FieldDefinition[]
	 */
	protected $fields;
	
	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return boolean
	 */
	public function getPrimary()
	{
		return $this->primary;
	}

	/**
	 * @return \Change\Db\Schema\FieldDefinition[]
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param string $name
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param boolean $primary
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function setPrimary($primary)
	{
		$this->primary = $primary;
		return $this;
	}

	/**
	 * @param \Change\Db\Schema\FieldDefinition[] $fields
	 * @return \Change\Db\Schema\KeyDefinition
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
		return $this;
	}
	
	/**
	 * @param \Change\Db\Schema\FieldDefinition $field
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function addField(\Change\Db\Schema\FieldDefinition $field)
	{
		$this->fields[$field->getName()] = $field;
		return $this;
	}
}