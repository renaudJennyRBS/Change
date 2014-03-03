<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Field;

/**
 * @api
 * @name \Rbs\Simpleform\Field\FieldTypeManager
 */
class FieldManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_Simpleform_FieldTypeManager';
	const EVENT_GET_FIELD_TYPE = 'getFieldType';
	const EVENT_GET_CODES = 'getCodes';

	/**
	 * @var \Change\Documents\Constraints\ConstraintsManager
	 */
	protected $constraintsManager;

	/**
	 * @param \Change\Documents\Constraints\ConstraintsManager $constraintsManager
	 * @return $this
	 */
	public function setConstraintsManager(\Change\Documents\Constraints\ConstraintsManager $constraintsManager)
	{
		$this->constraintsManager = $constraintsManager;
		return $this;
	}

	/**
	 * @return \Change\Documents\Constraints\ConstraintsManager
	 */
	protected function getConstraintsManager()
	{
		return $this->constraintsManager;
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach(static::EVENT_GET_FIELD_TYPE, array($this, 'onDefaultGetFieldType'), 5);
		$eventManager->attach(static::EVENT_GET_CODES, array($this, 'onDefaultGetCodes'), 5);
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getEventManagerFactory()->getConfiguredListenerClassNames('Rbs/Simpleform/Events/FieldTypeManager');
	}

	/**
	 * @api
	 * @param string $code
	 * @param array $params
	 * @return \Rbs\Simpleform\Field\FieldTypeInterface|null
	 */
	public function getFieldType($code, array $params = array())
	{
		// Instantiate constraint manager to register locales in validation.
		$this->getConstraintsManager();

		$em = $this->getEventManager();
		$args = $em->prepareArgs($params);

		$args['code'] = $code;

		$event = new \Change\Events\Event(static::EVENT_GET_FIELD_TYPE, $this, $args);
		$this->getEventManager()->trigger($event);

		$fieldType = $event->getParam('fieldType');
		if ($fieldType instanceof \Rbs\Simpleform\Field\FieldTypeInterface)
		{
			return $fieldType;
		}
		return null;
	}

	/**
	 * @api
	 * @param array $params
	 * @return array an associative array with the type code as key and an i18n key as value
	 */
	public function getCodes(array $params = array())
	{
		$em = $this->getEventManager();
		$args = $em->prepareArgs($params);

		$event = new \Change\Events\Event(static::EVENT_GET_CODES, $this, $args);
		$this->getEventManager()->trigger($event);

		$codes = $event->getParam('codes');
		if (is_array($codes))
		{
			return $codes;
		}
		return array();
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetFieldType(\Change\Events\Event $event)
	{
		$i18n = $event->getApplicationServices()->getI18nManager();
		$code = $event->getParam('code');
		$fieldType = null;
		switch ($code)
		{
			case 'Rbs_Simpleform_Hidden':
				$template = 'Rbs_Simpleform/Fields/hidden.twig';
				$converter = new \Rbs\Simpleform\Converter\Trim($i18n);
				break;

			case 'Rbs_Simpleform_Text_Input':
				$template = 'Rbs_Simpleform/Fields/text-input.twig';
				$converter = new \Rbs\Simpleform\Converter\Text($i18n);
				break;
			case 'Rbs_Simpleform_Text_Area':
				$template = 'Rbs_Simpleform/Fields/text-area.twig';
				$converter = new \Rbs\Simpleform\Converter\Trim($i18n);
				break;
			case 'Rbs_Simpleform_Text_Email':
				$template = 'Rbs_Simpleform/Fields/text-email.twig';
				$converter = new \Rbs\Simpleform\Converter\Email($i18n);
				break;
			case 'Rbs_Simpleform_Text_Emails':
				$template = 'Rbs_Simpleform/Fields/text-emails.twig';
				$converter = new \Rbs\Simpleform\Converter\Emails($i18n);
				break;
			case 'Rbs_Simpleform_Text_Url':
				$template = 'Rbs_Simpleform/Fields/text-url.twig';
				$converter = new \Rbs\Simpleform\Converter\Trim($i18n);
				break;
			case 'Rbs_Simpleform_Text_Integer':
				$template = 'Rbs_Simpleform/Fields/text-integer.twig';
				$converter = new \Rbs\Simpleform\Converter\Integer($i18n);
				break;
			case 'Rbs_Simpleform_Text_Float':
				$template = 'Rbs_Simpleform/Fields/text-float.twig';
				$converter = new \Rbs\Simpleform\Converter\Float($i18n);
				break;

			case 'Rbs_Simpleform_Boolean_Radio':
				$template = 'Rbs_Simpleform/Fields/boolean-radio.twig';
				$converter = new \Rbs\Simpleform\Converter\Boolean($i18n);
				break;
			case 'Rbs_Simpleform_Boolean_Checkbox':
				$template = 'Rbs_Simpleform/Fields/boolean-checkbox.twig';
				$converter = new \Rbs\Simpleform\Converter\Boolean($i18n);
				break;

			case 'Rbs_Simpleform_Collection_Select':
				$template = 'Rbs_Simpleform/Fields/collection-select.twig';
				$converter = new \Rbs\Simpleform\Converter\Trim($i18n);
				break;
			case 'Rbs_Simpleform_Collection_SelectMultiple':
				$template = 'Rbs_Simpleform/Fields/collection-select-multiple.twig';
				$converter = new \Rbs\Simpleform\Converter\TrimArray($i18n);
				break;
			case 'Rbs_Simpleform_Collection_Radio':
				$template = 'Rbs_Simpleform/Fields/collection-radio.twig';
				$converter = new \Rbs\Simpleform\Converter\Trim($i18n);
				break;
			case 'Rbs_Simpleform_Collection_Checkbox':
				$template = 'Rbs_Simpleform/Fields/collection-checkbox.twig';
				$converter = new \Rbs\Simpleform\Converter\TrimArray($i18n);
				break;

			case 'Rbs_Simpleform_Date_Picker':
				$template = 'Rbs_Simpleform/Fields/date-picker.twig';
				$converter = new \Rbs\Simpleform\Converter\Date($i18n);
				break;
			case 'Rbs_Simpleform_DateTime_Picker':
				$template = 'Rbs_Simpleform/Fields/date-time-picker.twig';
				$converter = new \Rbs\Simpleform\Converter\DateTime($i18n);
				break;

			case 'Rbs_Simpleform_File':
				$template = 'Rbs_Simpleform/Fields/file.twig';
				$converter = new \Rbs\Simpleform\Converter\File($i18n);
				break;

			default:
				return;
		}

		$fieldType = new \Rbs\Simpleform\Field\FieldType($code, $template, $converter);
		$event->setParam('fieldType', $fieldType);
		$event->stopPropagation();
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultGetCodes(\Change\Events\Event $event)
	{
		$codes = $event->getParam('codes', array());
		$codes = array_merge($codes, array(
			'Rbs_Simpleform_Hidden' => 'm.rbs.simpleform.admin.type_hidden',
			'Rbs_Simpleform_Text_Input' => 'm.rbs.simpleform.admin.type_text_input',
			'Rbs_Simpleform_Text_Area' => 'm.rbs.simpleform.admin.type_text_area',
			'Rbs_Simpleform_Text_Email' => 'm.rbs.simpleform.admin.type_text_email',
			'Rbs_Simpleform_Text_Emails' => 'm.rbs.simpleform.admin.type_text_emails',
			'Rbs_Simpleform_Text_Url' => 'm.rbs.simpleform.admin.type_text_url',
			'Rbs_Simpleform_Text_Integer' => 'm.rbs.simpleform.admin.type_text_integer',
			'Rbs_Simpleform_Text_Float' => 'm.rbs.simpleform.admin.type_text_float',
			'Rbs_Simpleform_Boolean_Radio' => 'm.rbs.simpleform.admin.type_boolean_radio',
			'Rbs_Simpleform_Boolean_Checkbox' => 'm.rbs.simpleform.admin.type_boolean_checkbox',
			'Rbs_Simpleform_Collection_Select' => 'm.rbs.simpleform.admin.type_collection_select',
			'Rbs_Simpleform_Collection_SelectMultiple' => 'm.rbs.simpleform.admin.type_collection_select_multiple',
			'Rbs_Simpleform_Collection_Radio' => 'm.rbs.simpleform.admin.type_collection_radio',
			'Rbs_Simpleform_Collection_Checkbox' => 'm.rbs.simpleform.admin.type_collection_checkbox',
			'Rbs_Simpleform_Date_Picker' => 'm.rbs.simpleform.admin.type_date_picker',
			'Rbs_Simpleform_DateTime_Picker' => 'm.rbs.simpleform.admin.type_date_time_picker',
			'Rbs_Simpleform_File' => 'm.rbs.simpleform.admin.type_file',
		));
		$event->setParam('codes', $codes);
	}
}