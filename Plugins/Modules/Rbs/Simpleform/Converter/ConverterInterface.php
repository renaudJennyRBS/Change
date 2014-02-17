<?php
namespace Rbs\Simpleform\Converter;

/**
 * @name \Rbs\Simpleform\Converter\ConverterInterface
 */
interface ConverterInterface
{
	/**
	 * @param \Change\I18n\I18nManager $i18nManager
	 */
	public function __Construct($i18nManager);

	/**
	 * @param mixed $value
	 * @param array $parameters
	 * @return mixed|\Rbs\Simpleform\Converter\Validation\Error JSON encodable value
	 */
	public function parseFromUI($value, $parameters);

	/**
	 * @param mixed $value
	 * @param array $parameters
	 * @return boolean
	 */
	public function isEmptyFromUI($value, $parameters);

	/**
	 * @param mixed $value
	 * @param array $parameters
	 * @return string
	 */
	public function formatValue($value, $parameters);
}