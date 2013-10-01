<?php
namespace Rbs\geo\Documents;

/**
 * @name \Rbs\geo\Documents\Address
 */
class Address extends \Compilation\Rbs\Geo\Documents\Address implements \Rbs\Geo\Interfaces\Address
{

	/**
	 * @return string[]
	 */
	public function getLines()
	{
		$lines =  array();
		$af = $this->getAddressFields();
		if ($af)
		{
			foreach ($af->getFieldsName() as $fieldName)
			{
				$value = $this->getFieldValue($fieldName);
				if ($value)
				{
					$lines[] = $value;
				}
			}
		}
		return $lines;
	}

	/**
	 * @param string $fieldName
	 * @return boolean
	 */
	public function hasField($fieldName)
	{
		$af = $this->getAddressFields();
		if ($af)
		{
			return in_array($fieldName, $af->getFieldsName());
		}
		return false;
	}

	/**
	 * @param string $fieldName
	 * @param mixed $value
	 * @return $this
	 */
	public function setFieldValue($fieldName, $value)
	{
		if ($this->hasField($fieldName))
		{
			$property = $this->getDocumentModel()->getProperty($fieldName);
			if ($property)
			{
				$property->setValue($this, $value);
			}
			else
			{
				$values = $this->getFieldsData();
				if (!is_array($values))
				{
					$values = array($fieldName => $value);
				}
				else
				{
					$values[$fieldName] = $value;
				}
				$this->setFieldsData($values);
			}
		}
		return $this;
	}

	/**
	 * @param string $fieldName
	 * @return mixed|null
	 */
	public function getFieldValue($fieldName)
	{
		if ($this->hasField($fieldName))
		{
			$property = $this->getDocumentModel()->getProperty($fieldName);
			if ($property)
			{
				return $property->getValue($this);
			}
			else
			{
				$values = $this->getFieldsData();
				if (is_array($values) && isset($values[$fieldName]))
				{
					return $values[$fieldName];
				}
			}
		}
		return null;
	}
}
