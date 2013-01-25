<?php
namespace Change\Db\Schema;

/**
 * @name \Change\Db\Schema\TableDefinition
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
	
	/**
	 * @var array
	 */
	protected $options = array();
	
	
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
	 * @param string $charset
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setCharset($charset)
	{
		$this->setOption('CHARSET', $charset);
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getCharset()
	{
		return $this->getOption('CHARSET');
	}
	
	/**
	 * @param string $collation
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setCollation($collation)
	{
		$this->setOption('COLLATION', $collation);
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getCollation()
	{
		return $this->getOption('COLLATION');
	}
}