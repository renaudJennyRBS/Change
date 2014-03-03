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
 * @name \Rbs\Simpleform\Converter\DateTime
 */
class DateTime extends \Rbs\Simpleform\Converter\AbstractConverter
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function parseFromUI($value, $parameters)
	{
		if (!is_array($value))
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.invalid_array', array('ucf'));
			return new Validation\Error(array($message));
		}

		if (!isset($value['date']) || trim($value['date']) === '')
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.must_select_date_and_time', array('ucf'));
			return new Validation\Error(array($message));
		}
		elseif (!isset($value['time']) || trim($value['time']) === '')
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.must_select_date_and_time', array('ucf'));
			return new Validation\Error(array($message));
		}

		$time = trim($value['time']);
		if (strlen($time) == 5)
		{
			$time .= ':00';
		}
		$validator = new \Zend\Validator\Date(array('format' => 'Y-m-d H:i:s'));
		$value = trim($value['date']) . ' ' . $time;
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
		return (!isset($value['date']) || trim($value['date']) === '') && (!isset($value['time']) || trim($value['time']) === '');
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return $this->getI18nManager()->transDateTime(new \DateTime($value));
	}
}