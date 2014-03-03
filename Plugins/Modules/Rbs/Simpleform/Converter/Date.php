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
 * @name \Rbs\Simpleform\Converter\Date
 */
class Date extends \Rbs\Simpleform\Converter\AbstractConverter
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function parseFromUI($value, $parameters)
	{
		$value = trim($value);
		if (!is_string($value))
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.invalid_string', array('ucf'));
			return new Validation\Error(array($message));
		}

		$validator = new \Zend\Validator\Date();
		$errorMessages = $this->getErrorMessages($validator, $value);

		return count($errorMessages) ? new Validation\Error($errorMessages) : $value;
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return boolean
	 */
	public function isEmptyFromUI($value, $parameters)
	{
		return trim($value) === '';
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return $this->getI18nManager()->transDate(new \DateTime($value));
	}
}