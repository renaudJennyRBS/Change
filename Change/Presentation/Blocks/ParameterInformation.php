<?php
namespace Change\Presentation\Blocks;

use Change\Documents\Property;

/**
 * @name \Change\Presentation\Blocks\ParameterInformation
 */
class ParameterInformation
{
	/**
	 * @var array
	 */
	protected $attributes;

	/**
	 * @param string $name
	 * @param string $type
	 * @param boolean $required
	 * @param mixed $defaultValue
	 */
	function __construct($name, $type = Property::TYPE_STRING, $required = false, $defaultValue = null)
	{
		$this->attributes['name'] = $name;
		$this->attributes['type'] = $type;
		$this->attributes['required'] = $required;
		$this->attributes['defaultValue']= $defaultValue;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->attributes['name'];
	}

	/**
	 * @param mixed|null $defaultValue
	 * @return $this
	 */
	public function setDefaultValue($defaultValue)
	{
		$this->attributes['defaultValue'] = $defaultValue;
		return $this;
	}

	/**
	 * @param boolean $required
	 * @return $this
	 */
	public function setRequired($required)
	{
		$this->attributes['required'] = $required;
		return $this;
	}

	/**
	 * If string assume comma separated string or json array string (start with '[')
	 * @param string|string[] $allowedModelsNames
	 * @return $this
	 */
	public function setAllowedModelsNames($allowedModelsNames)
	{
		if (is_string($allowedModelsNames))
		{
			if ($allowedModelsNames[0] === '[')
			{
				$allowedModelsNames = json_decode($allowedModelsNames, true);
			}
			else
			{
				$allowedModelsNames = explode(',', $allowedModelsNames);
			}
		}

		if (is_array($allowedModelsNames))
		{
			$this->attributes['allowedModelsNames'] = $allowedModelsNames;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->attributes;
	}
}