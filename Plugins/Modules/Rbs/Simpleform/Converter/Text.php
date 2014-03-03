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
 * @name \Rbs\Simpleform\Converter\Text
 */
class Text extends \Rbs\Simpleform\Converter\Trim
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function doParseFromUI($value, $parameters)
	{
		$errorMessages = array();
		foreach ($parameters as $name => $param)
		{
			$validator = null;
			switch ($name)
			{
				case 'maxSize':
					$param = intval($param);
					if ($param > 0)
					{
						$validator = new \Zend\Validator\StringLength(array('max' => $param));
					}
					break;

				case 'minSize':
					$param = intval($param);
					if ($param > 0)
					{
						$validator = new \Zend\Validator\StringLength(array('min' => $param));
					}
					break;

				case 'pattern':
					if (strlen($param))
					{
						$validator = new \Zend\Validator\Regex('/' . str_replace('/', '\/', $param) . '/');
					}
					break;
			}
			$errorMessages = array_merge($errorMessages, $this->getErrorMessages($validator, $value));
		}

		return count($errorMessages) ? new Validation\Error($errorMessages) : $value;
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return $value;
	}
}