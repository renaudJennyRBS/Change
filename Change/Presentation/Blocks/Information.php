<?php
namespace Change\Presentation\Blocks;

use Change\Documents\Property;

/**
 * @name \Change\Presentation\Blocks\Information
 */
class Information
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $section;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var ParameterInformation[]
	 */
	protected $parametersInformation = array();

	/**
	 * @param string $name
	 */
	function __construct($name)
	{
		$this->name = $name;
		list($vendor, $shortModuleName,) = explode('_', $name);
		$this->section = $vendor . '_' . $shortModuleName;
	}

	/**
	 * @api
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{

	}

	/**
	 * @param string $section
	 * @return $this
	 */
	public function setSection($section)
	{
		$this->section = $section;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSection()
	{
		return $this->section;
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
	public function getLabel()
	{
		return $this->label ? $this->label : $this->getName();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @param bool $required
	 * @param mixed $defaultValue
	 * @return ParameterInformation
	 */
	public function addInformationMeta($name, $type = Property::TYPE_STRING, $required = false, $defaultValue = null)
	{
		$parameterInformation = new ParameterInformation($name, $type, $required, $defaultValue);
		$key = $this->ucLower($name);
		$this->parametersInformation[$key] = $parameterInformation;
		return $parameterInformation;
	}

	/**
	 * @param integer $defaultValue
	 * @return ParameterInformation
	 */
	protected function addTTL($defaultValue)
	{
		$parameterInformation = $this->addInformationMeta('TTL', Property::TYPE_INTEGER, true, $defaultValue);
		return $parameterInformation;
	}

	/**
	 * @return ParameterInformation[]
	 */
	public function getParametersInformation()
	{
		return array_values($this->parametersInformation);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameterInformation($name)
	{
		$key = $this->ucLower($name);
		return isset($this->parametersInformation[$key]);
	}

	/**
	 * @param string $name
	 * @return ParameterInformation|null
	 */
	public function getParameterInformation($name)
	{
		$key = $this->ucLower($name);
		return isset($this->parametersInformation[$key]) ? $this->parametersInformation[$key] : null;
	}

	/**
	 * @param string $name
	 * @return ParameterInformation|null
	 */
	public function removeParameterInformation($name)
	{
		$key = $this->ucLower($name);
		if (isset($this->parametersInformation[$key]))
		{
			$parameter = $this->parametersInformation[$key];
			unset($this->parametersInformation[$key]);
			return $parameter;
		}
		return null;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function ucLower($name)
	{
		return strtolower($name[0]) . substr($name, 1);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = array('name' => $this->getName(), 'label' => $this->getLabel());
		$parameters = array();
		foreach($this->getParametersInformation() as $parameterInformation)
		{
			$parameters[] = $parameterInformation->toArray();
		}
		$array['parameters'] = $parameters;
		return $array;
	}
}