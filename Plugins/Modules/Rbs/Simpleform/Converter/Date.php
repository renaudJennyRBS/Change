<?php
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