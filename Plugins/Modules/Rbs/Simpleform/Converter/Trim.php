<?php
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\Trim
 */
class Trim extends \Rbs\Simpleform\Converter\AbstractConverter
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function parseFromUI($value, $parameters)
	{
		if (!is_string($value))
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.constraints.invalid-string', array('ucf'));
			return new Validation\Error(array($message));
		}
		return $this->doParseFromUI(trim($value), $parameters);
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	protected function doParseFromUI($value, $parameters)
	{
		return $value;
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
		return $value;
	}
}