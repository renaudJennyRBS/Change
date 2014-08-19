<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Rbs\Geo\Documents\AddressFields
 */
class AddressFields extends \Compilation\Rbs\Geo\Documents\AddressFields
{

	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach([Event::EVENT_CREATE, Event::EVENT_UPDATE], array($this, 'onDefaultCheckFields'), 5);
		$eventManager->attach(Event::EVENT_CREATE, array($this, 'onAddSystemFields'), 1);
	}

	/**
	 * @return string[]
	 */
	public function getSystemFieldsNames()
	{
		return ['zipCode', 'locality', 'territorialUnitCode', 'countryCode'];
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultCheckFields(Event $event)
	{
		if ($this !== $event->getDocument())
		{
			return;
		}
		$systemFieldsNames = $this->getSystemFieldsNames();
		$fields = $this->getFields();
		foreach ($fields as $field)
		{
			$code = $field->getCode();
			if ($code)
			{
				if (in_array($field->getCode(), $systemFieldsNames))
				{
					$field->setLocked(true);
				}
			}
			else
			{
				$field->setCode(uniqid('auto_'));
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onAddSystemFields(Event $event)
	{
		if ($this !== $event->getDocument())
		{
			return;
		}
		$systemFieldsNames = $this->getSystemFieldsNames();
		foreach ($systemFieldsNames as $fieldName)
		{
			$field = $this->getFieldByName($fieldName);
			if (!$field && $fieldName != 'territorialUnitCode')
			{
				$field = $this->newAddressField();
				$field->setLocked(true);
				$field->setLabel($fieldName);
				$field->setCode($fieldName);
				if ($fieldName == 'countryCode')
				{
					$field->setCollectionCode('Rbs_Geo_Collection_Countries');
				}
				$field->setRefLCID($this->getDocumentManager()->getLCID());
				$field->getRefLocalization()->setTitle($fieldName);
				$this->getFields()->add($field);
			}
		}
	}

	/**
	 * @param string $fieldName
	 * @return \Rbs\geo\Documents\AddressField|null
	 */
	public function getFieldByName($fieldName)
	{
		foreach ($this->getFields() as $field)
		{
			if ($field->getCode() == $fieldName) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * @return string[]
	 */
	public function getFieldsName()
	{
		$fieldsName = [];
		foreach ($this->getFields() as $field)
		{
			$fieldsName[] = $field->getCode();
		}
		return $fieldsName;
	}

	/**
	 * @return array
	 */
	public function getFieldsLayout()
	{
		$layout = $this->getFieldsLayoutData();
		if (!is_array($layout) || !count($layout))
		{
			$layout = array();
			foreach ($this->getFields() as $field)
			{
				$code = $field->getCode();
				$layout[] = array($code === 'countryCode' ? 'country' : $code);
			}
		}
		return $layout;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			/** @var $addressFields AddressFields */
			$addressFields = $event->getDocument();
			$restResult->setProperty('editorDefinition', $this->buildEditorDefinition($addressFields));
			$restResult->setProperty('fieldsLayout', $this->getFieldsLayout());
			$restResult->setProperty('systemFieldsNames', $this->getSystemFieldsNames());
		}
	}

	protected function processRestData($name, $value, \Change\Http\Event $event)
	{
		if ($name == 'fieldsLayout')
		{
			$this->setFieldsLayoutData($value, is_array($value) ? $value : null);
			return true;
		}
		return parent::processRestData($name, $value, $event);
	}

	/**
	 * @param AddressFields $addressFields
	 * @return array|null
	 */
	protected function buildEditorDefinition(AddressFields $addressFields)
	{
		$definition = ['fields' => []];
		foreach ($addressFields->getFields() as $addressField)
		{
			$def = array(
				'title' => $addressField->getTitle(),
				'code' => $addressField->getCode(),
				'required' => $addressField->getRequired(),
				'defaultValue' => $addressField->getDefaultValue(),
				'collectionCode' => $addressField->getCollectionCode()
			);
			$definition['fields'][] = $def;
		}
		if (count($definition['fields']))
		{
			return $definition;
		}
		return null;
	}
}
