<?php
namespace Rbs\Geo\Std;

use Rbs\Geo\Interfaces\Address as AddressInterface;

/**
 * @name \Rbs\Geo\Std\Address
 */
class Address implements AddressInterface
{
	/**
	 * @var array
	 */
	protected $fieldValues = array();

	/**
	 * @return string
	 */
	public function getCountryCode()
	{
		return $this->getFieldValue('countryCode');
	}

	/**
	 * @return string
	 */
	public function getZipCode()
	{
		return $this->getFieldValue('zipCode');
	}

	/**
	 * @return string
	 */
	public function getLocality()
	{
		return $this->getFieldValue('locality');
	}

	/**
	 * @return string[]
	 */
	public function getLines()
	{
		return array();
	}

	/**
	 * @param string $fieldPartName
	 * @return boolean
	 */
	public function hasField($fieldPartName)
	{
		return true;
	}

	/**
	 * @param string $fieldPartName
	 * @param mixed $value
	 * @return $this
	 */
	public function setFieldValue($fieldPartName, $value)
	{
		$this->fieldValues[$fieldPartName] = $value;
	}

	/**
	 * @param string $fieldPartName
	 * @return mixed|null
	 */
	public function getFieldValue($fieldPartName)
	{
		return isset($this->fieldValues[$fieldPartName]) ? $this->fieldValues[$fieldPartName] : null;
	}
}