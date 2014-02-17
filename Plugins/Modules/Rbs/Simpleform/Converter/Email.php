<?php
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\Emails
 */
class Email extends  \Rbs\Simpleform\Converter\Trim
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function doParseFromUI($value, $parameters)
	{
		$validator = new \Zend\Validator\EmailAddress();
		$errorMessages = $this->getErrorMessages($validator, $value);
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