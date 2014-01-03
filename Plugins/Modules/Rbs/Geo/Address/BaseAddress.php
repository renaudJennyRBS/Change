<?php
namespace Rbs\Geo\Address;

/**
 * @name \Rbs\Geo\Address\BaseAddress
 */
class BaseAddress implements AddressInterface
{
	/**
	 * @var array
	 */
	protected $fieldValues = array();

	/**
	 * @var array
	 */
	protected $layout = array();

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
	 * @return string
	 */
	public function getCountryCode()
	{
		return isset($this->fieldValues['countryCode']) ? $this->fieldValues['countryCode'] : null;
	}

	/**
	 * @return string
	 */
	public function getZipCode()
	{
		return isset($this->fieldValues['zipCode']) ? $this->fieldValues['zipCode'] : null;
	}

	/**
	 * @return string
	 */
	public function getLocality()
	{
		return isset($this->fieldValues['locality']) ? $this->fieldValues['locality'] : null;
	}

	/**
	 * @return string[]
	 */
	public function getLines()
	{
		$lines =  array();
		if (count($this->fieldValues) > 0)
		{
			if (count($this->layout))
			{
				foreach ($this->layout as $lineLayout)
				{
					$line = [];
					foreach ($lineLayout as $fieldName)
					{
						$fieldValue = $this->getFieldValue($fieldName);
						if ($fieldValue)
						{
							$line[] = $fieldValue;
						}
					}
					if (count($line))
					{
						$lines[] = implode(' ', $line);
					}
				}
			}
			else
			{
				foreach ($this->fieldValues as $fieldValue)
				{
					if ($fieldValue)
					{
						$lines[] = $fieldValue;
					}
				}
			}
		}
		return $lines;
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
		//check if a getter method exist for the field
		$getter = 'get' . ucfirst($fieldPartName);
		if (method_exists($this, $getter))
		{
			$fieldValue = $this->$getter();
		}
		else
		{
			$fieldValue = isset($this->fieldValues[$fieldPartName]) ? $this->fieldValues[$fieldPartName] : null;
		}
		return $fieldValue;
	}

	/**
	 * @param array $array
	 */
	public function fromArray($array)
	{
		foreach ($array as $addressField => $addressValue)
		{
			if ($addressField == '__layout')
			{
				$this->layout = $addressValue;
			}
			else
			{
				$this->setFieldValue($addressField, $addressValue);
			}
		}
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->getFields();
	}

	/**
	 * @param \Rbs\Geo\Address\AddressInterface $address
	 */
	public function fromAddress($address)
	{
		$this->setFieldValue('countryCode', $address->getCountryCode());
		$this->setFieldValue('zipCode', $address->getZipCode());
		$this->setFieldValue('locality', $address->getLocality());
		foreach ($address->getFields() as $addressField => $addressValue)
		{
			$this->setFieldValue($addressField, $addressValue);
		}
	}

	/**
	 * @param array $layout
	 */
	public function setLayout($layout)
	{
		$this->layout = $layout;
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		return $this->fieldValues;
	}
}