<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Constraints;

/**
 * @name \Change\Documents\Constraints\Min
 */
class Min extends \Zend\Validator\GreaterThan
{
	/**
	 * Returns true if and only if $value is greater or equals than min option
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->setValue($value);
		if ($this->min > $value) {
			$this->error(\Zend\Validator\GreaterThan::NOT_GREATER);
			return false;
		}
		return true;
	}
}