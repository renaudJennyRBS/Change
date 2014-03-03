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
 * @name \Rbs\Simpleform\Converter\Emails
 */
class Emails extends \Rbs\Simpleform\Converter\AbstractConverter
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string[]|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function parseFromUI($value, $parameters)
	{
		$emails = array_map('trim', explode(',', $value));
		$validator = new \Zend\Validator\EmailAddress();
		$validator->setTranslatorTextDomain('m.rbs.simpleform.constraints');
		$errorMessages = array();
		foreach ($emails as $email)
		{
			if (!$validator->isValid($email))
			{
				$errorMessages = array_merge($errorMessages, array_values($validator->getMessages()));
			}
		}

		return count($errorMessages) ? new Validation\Error($errorMessages) : $emails;
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
	 * @param array $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return implode(', ', $value);
	}
}