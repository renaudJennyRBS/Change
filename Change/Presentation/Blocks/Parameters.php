<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks;

use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Change\Presentation\Blocks\Parameters
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
	 * @return integer
	 */
	public function getTTL()
	{
		$TTL = $this->getParameter('TTL');
		return $TTL === null ? 60 : $TTL;
	}

	/**
	 * Set to 0 for no cache
	 * @param int $TTL
	 * @return $this
	 */
	public function setTTL($TTL = 60)
	{
		$TTL = max(0, $TTL);
		if (!$this->hasParameterMeta('TTL'))
		{
			$this->addParameterMeta('TTL', $TTL);
		}
		else
		{
			$this->getParameterMeta('TTL')->setDefaultValue($TTL);
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function setNoCache()
	{
		return $this->setTTL(0);
	}

	/**
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return ParameterMeta
	 */
	public function addParameterMeta($name, $defaultValue = null)
	{
		$parameterMeta = new ParameterMeta($name, $defaultValue);
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
				if ($name === 'TTL')
				{
					$this->setParameterValue($name, intval($value));
				}
				else
				{
					$meta = $this->getParameterMeta($name);
					if ($meta)
					{
						if ($meta->getDefaultValue() != $value)
						{
							$this->setParameterValue($name, $value);
						}
					}
					else
					{
						$this->setParameterValue($name, $value);
					}
				}
			}
		}
	}

	/**
	 * @param \Change\Presentation\Layout\Block $blockLayout
	 */
	public function setLayoutParameters(\Change\Presentation\Layout\Block $blockLayout = null)
	{
		if ($blockLayout !== null)
		{
			$this->setUpdatedParametersValue($blockLayout->getParameters());
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
	 * @return boolean
	 */
	function __isset($name)
	{
		return ($this->hasParameterValue($name) || $this->hasParameterMeta($name));
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 * @throws \InvalidArgumentException
	 */
	function __get($name)
	{
		if ($this->hasParameterValue($name))
		{
			return $this->getParameterValue($name);
		}
		elseif($this->hasParameterMeta($name))
		{
			return $this->getParameterMeta($name)->getDefaultValue();
		}
		throw new \InvalidArgumentException('Argument 1 must be a valid parameter name: ' . $name, 999999);
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
		$array = [];
		foreach ($this->parametersMeta as $parameterMeta)
		{
			$array[$parameterMeta->getName()] = $this->getParameter($parameterMeta->getName());
		}
		return $array;
	}
}