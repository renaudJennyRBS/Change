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
 * @name \Change\Documents\Constraints\Max
 */
class Max extends \Zend\Validator\LessThan
{
	/**
	 * Defined by \Zend\Validator\ValidatorInterface
	 *
	 * Returns true if and only if $value is less or equals than max option
	 *
	 * @param  mixed $value
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->setValue($value);
		if ($this->max < $value) {
			$this->error(\Zend\Validator\LessThan::NOT_LESS);
			return false;
		}
		return true;
	}	
}