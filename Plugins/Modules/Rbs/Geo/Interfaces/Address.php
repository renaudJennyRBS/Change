<?php
namespace Rbs\Geo\Interfaces;

/**
 * @name \Rbs\Geo\Interfaces\Address
 */
interface Address
{
	/**
	 * @return string
	 */
	public function getCountryCode();

	/**
	 * @return string
	 */
	public function getZipCode();

	/**
	 * @return string
	 */
	public function getLocality();

	/**
	 * @return string[]
	 */
	public function getLines();

	/**
	 * @param string $fieldPartName
	 * @return boolean
	 */
	public function hasField($fieldPartName);

	/**
	 * @param string $fieldPartName
	 * @param mixed $value
	 * @return $this
	 */
	public function setFieldValue($fieldPartName, $value);

	/**
	 * @param string $fieldPartName
	 * @return mixed|null
	 */
	public function getFieldValue($fieldPartName);
}