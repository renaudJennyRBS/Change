<?php
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