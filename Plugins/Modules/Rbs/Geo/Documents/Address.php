<?php
namespace Rbs\geo\Documents;

use Change\Documents\Events\Event as DocumentEvent;

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

	public function setFieldValues($fieldValues)
	{
		if (is_array($fieldValues))
		{
			foreach($fieldValues as $fieldName => $value)
			{
				$this->setFieldValue($fieldName, $value);
			}
		}
	}

	public function getFieldValues()
	{
		$fieldValues = array();
		$af = $this->getAddressFields();
		foreach($af->getFieldsName() as $fieldName)
		{
			$fieldValues[$fieldName] = $this->getFieldValue($fieldName);
		}
		return $fieldValues;
	}

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}


	public function onDefaultSave(DocumentEvent $event)
	{
		/* @var $address Address */
		$address = $event->getDocument();

		$propertiesErrors = $event->getParam('propertiesErrors');
		if (!is_array($propertiesErrors))
		{
			$propertiesErrors = array();
		}

		$constraintManager = $event->getApplicationServices()->getConstraintsManager();
		foreach ($address->getAddressFields()->getFields() as $addressField)
		{
			$match = $addressField->getMatch();
			if(!$match)
			{
				continue;
			}
			$c = $constraintManager->matches($match);
			$fieldName = $addressField->getCode();
			if(!$c->isValid($address->getFieldValue($fieldName)))
			{
				foreach($c->getMessages() as $error)
				{
					if ($error !== null)
					{
						$propertiesErrors[$fieldName][] = $error;
					}
				}
			}
		}

		$event->setParam('propertiesErrors', count($propertiesErrors) ? $propertiesErrors : null);
	}

	public function getLabel()
	{
		return $this->getName();
	}

	public function setLabel($value)
	{
		// not implemented
	}
}
