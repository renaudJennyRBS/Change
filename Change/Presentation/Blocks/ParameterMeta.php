<?php
namespace Change\Presentation\Blocks;

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
	 * @var mixed|null
	 */
	protected $defaultValue;

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 */
	function __construct($name, $defaultValue = null)
	{
		$this->name = $name;
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
}