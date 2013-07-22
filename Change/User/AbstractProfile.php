<?php
namespace Change\User;

/**
* @name \Change\User\AbstractProfile
*/
abstract class AbstractProfile implements ProfileInterface
{
	/**
	 * @var array
	 */
	protected $properties = array();

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		if (in_array($name, $this->getPropertyNames()) && isset($this->properties[$name]))
		{
			return $this->properties[$name];
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setPropertyValue($name, $value)
	{
		if (in_array($name, $this->getPropertyNames()))
		{
			$this->properties[$name] = $value;
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->properties;
	}
}