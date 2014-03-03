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
 * @name \Rbs\Simpleform\Converter\Float
 */
class Float extends \Rbs\Simpleform\Converter\Trim
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return float|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function doParseFromUI($value, $parameters)
	{
		if (is_numeric($value))
		{
			$parsed = floatval($value);
		}
		elseif (!$this->i18nManager)
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.invalid_float', array('ucf'));
			return new Validation\Error(array($message));
		}
		else
		{
			$formatter = new \NumberFormatter($this->i18nManager->getLCID(), \NumberFormatter::DECIMAL);
			$position = 0;
			$parsed = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);
			if ($parsed === false || $position != strlen($value))
			{
				$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.invalid_float', array('ucf'));
				return new Validation\Error(array($message));
			}
		}

		$errorMessages = array();
		foreach ($parameters as $name => $param)
		{
			if ($param === '' || $param === null)
			{
				continue;
			}

			$validator = null;
			switch ($name)
			{
				case 'max':
					$validator = new \Zend\Validator\LessThan(array('max' => floatval($param), 'inclusive' => true));
					break;

				case 'min':
					$validator = new \Zend\Validator\GreaterThan(array('min' => floatval($param), 'inclusive' => true));
					break;
			}
			$errorMessages = array_merge($errorMessages, $this->getErrorMessages($validator, $parsed));
		}

		return count($errorMessages) ? new Validation\Error($errorMessages) : $parsed;
	}

	/**
	 * @param float $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		if (!$this->i18nManager)
		{
			return strval($value);
		}
		$formatter = new \NumberFormatter($this->i18nManager->getLCID(), \NumberFormatter::TYPE_DOUBLE);
		return $formatter->format($value);
	}
}