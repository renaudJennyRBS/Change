<?php
namespace Change\Db\Schema;

/**
 * @name \Change\Db\Schema\FieldDefinition
 */
class FieldDefinition
{	
	const CHAR = 0;
	const VARCHAR  = 1;
	
	const INTEGER  = 2;
	const SMALLINT = 3;
	
	const DECIMAL = 4;
	const FLOAT = 5;
	
	const DATE = 6;
	const TIMESTAMP = 7;
	
	const ENUM = 8;
	
	const LOB = 9;
	
	const TEXT = 10;
	
	/**
	 * @var array
	 */
	protected $options = array();
	
	/**
	 * @var string
	 */
	protected $name;
	
	/**
	 * @var integer
	 */
	protected $type = self::VARCHAR;
	
	/**
	 * @var string
	 */
	protected $defaultValue = null;

	/**
	 * @var boolean
	 */
	protected $nullable = true;
	
	/**
	 * @param string $name
	 */
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
	 * @param string $name
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	

	/**
	 * @return integer FieldDefinition::*
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * @param integer $type FieldDefinition::*
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
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
	 * @return boolean
	 */
	public function getNullable()
	{
		return $this->nullable;
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
	 * @param integer $length
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setLength($length)
	{
		$this->setOption('LENGTH', intval($length));
		return $this;
	}
	
	/**
	 * @return integer
	 */
	public function getLength()
	{
		return intval($this->getOption('LENGTH'));
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
	
	/**
	 * @param boolean $autoNumber
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setAutoNumber($autoNumber)
	{
		$this->setOption('AUTONUMBER', ($autoNumber == true));
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function getAutoNumber()
	{
		return $this->getOption('AUTONUMBER') === true;
	}
	
	/**
	 * @param integer $precision
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setPrecision($precision)
	{
		$this->setOption('PRECISION', intval($precision));
		return $this;
	}
	
	/**
	 * @return integer
	 */
	public function getPrecision()
	{
		return intval($this->getOption('PRECISION'));
	}
	
	/**
	 * @param integer $scale
	 * @return \Change\Db\Schema\FieldDefinition
	 */
	public function setScale($scale)
	{
		$this->setOption('SCALE', intval($scale));
		return $this;
	}
	
	/**
	 * @return integer
	 */
	public function getScale()
	{
		return intval($this->getOption('SCALE'));
	}
}