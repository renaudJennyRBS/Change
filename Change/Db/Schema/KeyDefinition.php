<?php
namespace Change\Db\Schema;

/** 
 * @name \Change\Db\Schema\KeyDefinition
 */
class KeyDefinition
{
	const PRIMARY = 'PRIMARY';
	
	const UNIQUE = 'UNIQUE';
	
	const INDEX = 'INDEX';
	
	/**
	 * @var string
	 */
	protected $type = self::INDEX;
	
	/**
	 * @var array
	 */
	protected $options = array();	
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var \Change\Db\Schema\FieldDefinition[]
	 */
	protected $fields;
	
	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function setType($type)
	{
		if ($type === static::PRIMARY || $type === static::UNIQUE)
		{
			$this->type = $type;
		}
		else
		{
			$this->type = static::INDEX;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param array $options
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function setOptions($options)
	{
		$this->options = is_array($options) ? $options :  array();
		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function setOption($name, $value)
	{
		if ($value === null)
		{
			unset($this->options[$name]);
		}
		else
		{
			$this->options[$name] = $value;
		}
		return $this;
	}
	
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getOption($name)
	{
		return isset($this->options[$name]) ? $this->options[$name] : null;
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
	 * @return \Change\Db\Schema\KeyDefinition
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isPrimary()
	{
		return $this->type === static::PRIMARY;
	}
	
	/**
	 * @return boolean
	 */
	public function isUnique()
	{
		return $this->type === static::UNIQUE;
	}
	
	/**
	 * @return boolean
	 */
	public function isIndex()
	{
		return $this->type === static::INDEX;
	}
	
	/**
	 * @return \Change\Db\Schema\FieldDefinition[]
	 */
	public function getFields()
	{
		return $this->fields;
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