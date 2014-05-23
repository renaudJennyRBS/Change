<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\geo\Documents;

use Change\Documents\Events\Event as DocumentEvent;
use Change\Documents\Events;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Geo\Documents\Address
 */
class Address extends \Compilation\Rbs\Geo\Documents\Address implements \Rbs\Geo\Address\AddressInterface
{
	/**
	 * @var array
	 */
	protected $fieldValues;

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
	protected function setFieldValue($fieldName, $value)
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
	protected function getFieldValue($fieldName)
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

	/**
	 * @param array $fieldValues
	 * @return $this
	 */
	public function setFieldValues($fieldValues)
	{
		$this->fieldValues = is_array($fieldValues) ? $fieldValues : [];
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFieldValues()
	{
		if (!is_array($this->fieldValues))
		{
			$values = $this->getFieldsData();
			$this->fieldValues = is_array($values) ? $values : [];
		}
		return $this->fieldValues;
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(array(DocumentEvent::EVENT_CREATE, DocumentEvent::EVENT_UPDATE), array($this, 'onDefaultSave'), 10);
	}

	/**
	 * @param DocumentEvent $event
	 */
	public function onDefaultSave(DocumentEvent $event)
	{
		/* @var $address Address */
		$address = $event->getDocument();

		$fieldValues = $address->fieldValues;
		if (is_array($fieldValues))
		{
			$cleanFieldValues = [];
			$af = $address->getAddressFields();
			if ($af)
			{
				$constraintManager = $event->getApplicationServices()->getConstraintsManager();
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$propertiesErrors = $event->getParam('propertiesErrors');
				if (!is_array($propertiesErrors))
				{
					$propertiesErrors = array();
				}

				foreach ($af->getFields() as $addressField)
				{
					$fieldName = $addressField->getCode();
					$value = (isset($fieldValues[$fieldName])) ? $fieldValues[$fieldName] : null;
					if ($value === null && $addressField->getRequired())
					{
						$propertiesErrors[$fieldName][] = $i18nManager->trans('c.constraints.isempty', array('ucf'));
						continue;
					}
					$match = $addressField->getMatch();
					if ($match && $value !== null)
					{
						$c = $constraintManager->matches($match);
						if (!$c->isValid($value))
						{
							foreach($c->getMessages() as $error)
							{
								if ($error !== null)
								{
									$propertiesErrors[$fieldName][] = $error;
								}
							}
							continue;
						}
					}

					$cleanFieldValues[$fieldName] = $value;
					$property = $address->getDocumentModel()->getProperty($fieldName);
					if ($property)
					{
						$property->setValue($address, $value);
					}
				}
				$event->setParam('propertiesErrors', count($propertiesErrors) ? $propertiesErrors : null);
			}

			$genericServices = $event->getServices('genericServices');
			if ($genericServices instanceof \Rbs\Generic\GenericServices)
			{
				$cleanFieldValues['__lines'] = $genericServices->getGeoManager()->getFormattedAddress($address);
			}

			$address->setFieldsData(count($cleanFieldValues) ? $cleanFieldValues : null);
			$address->fieldValues = $cleanFieldValues;
		}
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		$array = $this->getFieldValues();
		$array['countryCode'] = $this->getCountryCode();
		$array['zipCode'] = $this->getZipCode();
		$array['locality'] = $this->getLocality();
		return $array;

	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		return $this->getName();
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setLabel($value)
	{
		// not implemented
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getLines()
	{
		$values = $this->getFieldsData();
		return (is_array($values) && isset($values['__lines']) && is_array($values['__lines'])) ? $values['__lines'] : [];
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$array = $this->getFields();
		$array['__id'] = $this->getId();
		$array['__addressFieldsId'] = $this->getAddressFieldsId();
		return $array;
	}

	/**
	 * @param Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$address = $event->getDocument();
		if (!$address instanceof Address)
		{
			return;
		}
		$documentResult = $event->getParam('restResult');
		if ($documentResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$pc = new \Change\Http\Rest\V1\ValueConverter($documentResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
			$documentResult->setProperty('fieldValues', $pc->toRestValue($address->getFields(), \Change\Documents\Property::TYPE_JSON));
			$documentResult->setProperty('lines', $address->getLines());
		}
		elseif ($documentResult instanceof \Change\Http\Rest\V1\Resources\DocumentLink)
		{
			$extraColumn = $event->getParam('extraColumn');
			if (is_array($extraColumn) && in_array('fieldValues', $extraColumn)) {
				$pc = new \Change\Http\Rest\V1\ValueConverter($documentResult->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
				$documentResult->setProperty('fieldValues', $pc->toRestValue($address->getFields(), \Change\Documents\Property::TYPE_JSON));
			}
		}
	}

	/**
	 * Process the incoming REST data $name and set it to $value
	 * @param string $name
	 * @param mixed $value
	 * @param \Change\Http\Event $event
	 * @return boolean
	 */
	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		if ($name === 'fieldValues')
		{
			$pc = new \Change\Http\Rest\V1\ValueConverter($event->getUrlManager(), $event->getApplicationServices()->getDocumentManager());
			$this->fieldValues = $pc->toPropertyValue($value, \Change\Documents\Property::TYPE_JSON);
			return true;
		}
		else
		{
			return parent::processRestData($name, $value, $event);
		}
	}
}
