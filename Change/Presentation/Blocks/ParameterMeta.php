<?php
namespace Change\Presentation\Blocks;

use Change\Documents\Property;

/**
 * @name \Change\Presentation\Blocks\ParameterMeta
 */
class ParameterMeta
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $type = Property::TYPE_STRING;

	/**
	 * @var boolean
	 */
	protected $required = false;

	/**
	 * @var mixed|null
	 */
	protected $defaultValue;

	/**
	 * @param string $name
	 * @param string $type
	 * @param boolean $required
	 * @param mixed $defaultValue
	 */
	function __construct($name, $type = Property::TYPE_STRING, $required = false, $defaultValue = null)
	{
		$this->name = $name;
		$this->type = $type;
		$this->required = $required;
		$this->defaultValue = $defaultValue;
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
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed|null $defaultValue
	 * @return $this
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->defaultValue = $defaultValue;
		return $this;
	}

	/**
	 * @return mixed|null
	 */
	public function getDefaultValue()
	{
		return $this->defaultValue;
	}

	/**
	 * @param boolean $required
	 * @return $this
	 */
	public function setRequired($required)
	{
		$this->required = $required;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getRequired()
	{
		return $this->required;
	}
}