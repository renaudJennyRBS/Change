<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\Address\BaseAddress
 */
class BaseAddress implements AddressInterface
{
	const LINES_FIELD_NAME = '__lines';

	/**
	 * @var array
	 */
	protected $fieldValues = array();

	/**
	 * @param $data \Rbs\Geo\Address\AddressInterface|array
	 * @return \Rbs\Geo\Address\BaseAddress
	 */
	public function __construct($data = null)
	{
		if ($data instanceof \Rbs\Geo\Address\AddressInterface)
		{
			$this->fromAddress($data);
		}
		elseif (is_array($data))
		{
			$this->fromArray($data);
		}
	}

	/**
	 * @return string|null
	 */
	public function getCountryCode()
	{
		return $this->getFieldValue(AddressInterface::COUNTRY_CODE_FIELD_NAME);
	}

	/**
	 * @return string|null
	 */
	public function getZipCode()
	{
		return $this->getFieldValue(AddressInterface::ZIP_CODE_FIELD_NAME);
	}

	/**
	 * @return string|null
	 */
	public function getLocality()
	{
		return $this->getFieldValue(AddressInterface::LOCALITY_FIELD_NAME);
	}

	/**
	 * @return string[]
	 */
	public function getLines()
	{
		$lines = $this->getFieldValue(static::LINES_FIELD_NAME);
		return is_array($lines) ? $lines : [];
	}

	/**
	 * @param string[] $lines
	 * @return $this
	 */
	public function setLines($lines)
	{
		$this->setFieldValue(static::LINES_FIELD_NAME, (is_array($lines)) ? $lines : null);
		return $this;
	}

	/**
	 * @param string $fieldPartName
	 * @param mixed $value
	 * @return $this
	 */
	public function setFieldValue($fieldPartName, $value)
	{
		$this->fieldValues[$fieldPartName] = $value;
		return $this;
	}

	/**
	 * @param string $fieldPartName
	 * @param null $defaultValue
	 * @return mixed|null
	 */
	public function getFieldValue($fieldPartName, $defaultValue = null)
	{
		return isset($this->fieldValues[$fieldPartName]) ? $this->fieldValues[$fieldPartName] : $defaultValue;
	}

	/**
	 * @param array $array
	 */
	public function fromArray($array)
	{
		$this->fieldValues = [];
		if (is_array($array) || ($array instanceof \Traversable))
		{
			foreach ($array as $addressField => $addressValue)
			{
				if (is_array($addressValue)) {
					if ($addressField === 'lines') {
						$this->setFieldValue('__lines', $addressValue);
					} elseif ($addressField === 'common') {
						foreach ($addressValue as $sysField => $sysValue)
						{
							$this->setFieldValue('__' . $sysField, $sysValue);
						}
					} elseif ($addressField === 'fields') {
						foreach ($addressValue as $fieldName => $fieldValue)
						{
							$this->setFieldValue($fieldName, $fieldValue);
						}
					}
					elseif ($addressField === 'default')
					{
						$this->setFieldValue('__default', $addressValue);
					} else {
						$this->setFieldValue($addressField, $addressValue);
					}
				}
				else {
					$this->setFieldValue($addressField, $addressValue);
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = [];
		foreach ($this->getFields() as $name => $value)
		{
			switch($name)
			{
				case '__lines':
					$array['lines'] = is_array($value) ? $value : [];
					break;
				case '__default':
					$array['default'] = is_array($value) ? $value : [];
					break;
				default:
					if (strpos($name, '__') === 0)
					{
						$array['common'][substr($name, 2)] = $value;
					}
					else
					{
						$array['fields'][$name] = $value;
					}
			}
		}
		return $array;
	}

	/**
	 * @return array
	 */
	public function toFlatArray()
	{
		return $this->getFields();
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface $address
	 */
	public function fromAddress($address)
	{
		$this->fieldValues = [];
		foreach ($address->getFields() as $addressField => $addressValue)
		{
			$this->setFieldValue($addressField, $addressValue);
		}
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fieldValues;
	}
}