<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\Boolean
 */
class Boolean extends \Rbs\Simpleform\Converter\Trim
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return boolean|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function doParseFromUI($value, $parameters)
	{
		if ($value === 'true')
		{
			return true;
		}
		elseif ($value === 'false')
		{
			return false;
		}
		$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.invalid_boolean', array('ucf'));
		return new Validation\Error(array($message));
	}

	/**
	 * @param boolean $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return $this->getI18nManager()->trans('m.rbs.generic.' . ($value === true ? 'yes' : 'no'), array('ucf'));
	}
}