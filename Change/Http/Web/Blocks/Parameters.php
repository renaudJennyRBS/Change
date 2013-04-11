<?php
namespace Change\Http\Web\Blocks;

use Change\Documents\Property;

/**
 * @name \Change\Http\Web\Blocks\Parameters
 */
class Parameters
{

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var ParameterMeta[]
	 */
	protected $parametersMeta = array();

	/**
	 * @var array
	 */
	protected $parameters = array();


	function __construct($name)
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
	 * @param string $type
	 * @param bool $required
	 * @param mixed $defaultValue
	 * @return ParameterMeta
	 */
	public function addParameterMeta($name, $type = Property::TYPE_STRING, $required = false, $defaultValue = null)
	{
		$parameterMeta = new ParameterMeta($name, $type, $required, $defaultValue);
		$key = $this->ucLower($name);
		$this->parametersMeta[$key] = $parameterMeta;
		return $parameterMeta;
	}

	/**
	 * @return ParameterMeta[]
	 */
	public function getParametersMetaArray()
	{
		return array_values($this->parametersMeta);
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameterMeta($name)
	{
		$key = $this->ucLower($name);
		return isset($this->parametersMeta[$key]);
	}

	/**
	 * @param string $name
	 * @return ParameterMeta|null
	 */
	public function getParameterMeta($name)
	{
		$key = $this->ucLower($name);
		return isset($this->parametersMeta[$key]) ? $this->parametersMeta[$key] : null;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasParameterValue($name)
	{
		$key = $this->ucLower($name);
		return array_key_exists($key, $this->parameters);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getParameterValue($name)
	{
		$key = $this->ucLower($name);
		return array_key_exists($key, $this->parameters) ? $this->parameters[$key] : null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setParameterValue($name, $value)
	{
		$key = $this->ucLower($name);
		$this->parameters[$key] = $value;
		return $this;
	}

	/**
	 * @param array $parameters
	 */
	public function setUpdatedParametersValue($parameters)
	{
		if (is_array($parameters) && count($parameters))
		{
			foreach($parameters as $name => $value)
			{
				if (($meta = $this->getParameterMeta($name)) !== null && $meta->getDefaultValue() != $value)
				{
					$this->setParameterValue($name, $value);
				}
			}
		}
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function resetParameterValue($name)
	{
		if ($name === null || $name === '*')
		{
			$this->parameters = array();
		}
		else
		{
			unset($this->parameters[$this->ucLower($name)]);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getParameter($name)
	{
		if ($this->hasParameterValue($name))
		{
			return $this->getParameterValue($name);
		}
		elseif($this->hasParameterMeta($name))
		{
			return $this->getParameterMeta($name)->getDefaultValue();
		}
		return null;
	}

	function __call($name, $arguments)
	{
		if (count($arguments) === 0)
		{
			if (preg_match('/^get([A-Za-z0-9_]+)Meta$/', $name, $matches))
			{
				return $this->getParameterMeta($matches[1]);
			}
			elseif(preg_match('/^get([A-Za-z0-9_]+)Value$/', $name, $matches))
			{
				return $this->getParameterValue($matches[1]);
			}
			elseif(preg_match('/^get([A-Za-z0-9_]+)$/', $name, $matches))
			{
				return $this->getParameter($matches[1]);
			}
			elseif(preg_match('/^reset([A-Za-z0-9_]+)$/', $name, $matches))
			{
				return $this->resetParameterValue($matches[1]);
			}
		}
		elseif (count($arguments) === 1)
		{
			if (preg_match('/^set([A-Za-z0-9_]+)Value$/', $name, $matches))
			{
				return $this->setParameterValue($matches[1], $arguments[0]);
			}
		}
		throw new \BadFunctionCallException(get_class($this) . '->' . $name. ' with ' . count($arguments) . ' arguments.', 999999);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	protected function ucLower($name)
	{
		return strtolower($name[0]) . substr($name, 1);
	}
}