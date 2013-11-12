<?php
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\TrimArray
 */
class TrimArray extends \Rbs\Simpleform\Converter\AbstractConverter
{
	/**
	 * @param array $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function parseFromUI($value, $parameters)
	{
		if (!is_array($value))
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.constraints.invalid-array', array('ucf'));
			return new Validation\Error(array($message));
		}
		$parsed = array();
		foreach ($value as $singleValue)
		{
			if (!is_string($singleValue))
			{
				$message = $this->getI18nManager()->trans('m.rbs.simpleform.constraints.invalid-array', array('ucf'));
				return new Validation\Error(array($message));
			}
			$singleValue = trim($singleValue);
			if ($singleValue !== '')
			{
				$parsed[] = $this->doParseValueFromUI(trim($singleValue), $parameters);
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
				case 'maxCount':
					$max = intval($param);
					if (count($parsed) > $max)
					{
						$errorMessages[] = $this->getI18nManager()->trans('m.rbs.simpleform.constraints.not-more-than-values',
							array('ucf'), array('max' => $max));
					}
					break;

				case 'minCount':
					$min = intval($param);
					if (count($parsed) < $min)
					{
						$errorMessages[] = $this->getI18nManager()->trans('m.rbs.simpleform.constraints.not-less-than-values',
							array('ucf'), array('min' => $min));
					}
					break;
			}
		}

		return count($errorMessages) ? new Validation\Error($errorMessages) : $parsed;
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string|\Rbs\Simpleform\Converter\Validation\Error
	 */
	protected function doParseValueFromUI($value, $parameters)
	{
		return $value;
	}

	/**
	 * @param array $value
	 * @param array $parameters
	 * @return boolean
	 */
	public function isEmptyFromUI($value, $parameters)
	{
		if (is_array($value))
		{
			foreach ($value as $singleValue)
			{
				if (trim($singleValue) !== '')
				{
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return implode(', ', $value);
	}
}