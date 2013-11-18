<?php
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