<?php
namespace Rbs\Simpleform\Field;

/**
 * @name \Rbs\Simpleform\Field\FieldTypeInterface
 */
interface FieldTypeInterface
{
	/**
	 * @return string
	 */
	public function getCode();

	/**
	 * @return string
	 */
	public function getTemplateName();

	/**
	 * @return \Rbs\Simpleform\Converter\ConverterInterface
	 */
	public function getConverter();
}