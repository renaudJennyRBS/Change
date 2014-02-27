<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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