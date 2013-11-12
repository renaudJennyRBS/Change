<?php
namespace Rbs\Simpleform\Field;

/**
 * @name \Rbs\Simpleform\Field\FieldInterface
 */
interface FieldInterface
{
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return string
	 */
	public function getFieldTypeCode();

	/**
	 * @return array
	 */
	public function getParameters();

	/**
	 * @return boolean
	 */
	public function getRequired();
}