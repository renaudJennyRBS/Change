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
 * @name \Change\User\ProfileInterface
 */
interface ProfileInterface
{

	public function getName();

	/**
	 * @return string[]
	 */
	public function getPropertyNames();

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name);

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setPropertyValue($name, $value);
}