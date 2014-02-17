<?php
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\File
 */
class File extends \Rbs\Simpleform\Converter\AbstractConverter
{
	/**
	 * @param string $value
	 * @param array $parameters
	 * @return \Rbs\Simpleform\Converter\File\TmpFile|\Rbs\Simpleform\Converter\Validation\Error
	 */
	public function parseFromUI($value, $parameters)
	{
		$file = new \Rbs\Simpleform\Converter\File\TmpFile($value);
		if ($file->getError() !== 0)
		{
			$message = $this->getI18nManager()->trans('m.rbs.simpleform.front.invalid_file', array('ucf'));
			return new Validation\Error(array($message));
		}
		return $file;
	}

	/**
	 * @param string $value
	 * @param array $parameters
	 * @return boolean
	 */
	public function isEmptyFromUI($value, $parameters)
	{
		return !(is_array($value) && isset($value['size']) && $value['size'] > 0);
	}

	/**
	 * @param \Rbs\Simpleform\Converter\File\TmpFile $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters)
	{
		return $value->getName();
	}
}