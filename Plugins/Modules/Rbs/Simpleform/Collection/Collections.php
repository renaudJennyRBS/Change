<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Collection;

use Change\I18n\I18nString;

/**
 * @name \Rbs\Simpleform\Collection\Collections
 */
class Collections
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function addConfirmationModes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array(
				'message' => new I18nString($i18n, 'm.rbs.simpleform.admin.form_confirmationmode_message', array('ucf')),
				'popin' => new I18nString($i18n, 'm.rbs.simpleform.admin.form_confirmationmode_popin', array('ucf')),
				'page' => new I18nString($i18n, 'm.rbs.simpleform.admin.form_confirmationmode_page', array('ucf'))
			);
			$collection = new \Change\Collection\CollectionArray('Rbs_Simpleform_ConfirmationModes', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function addFieldTypes(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		$genericServices = $event->getServices('genericServices');
		if ($applicationServices instanceof \Change\Services\ApplicationServices && $genericServices instanceof \Rbs\Generic\GenericServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = array();
			$fieldManager = $genericServices->getFieldManager();
			foreach ($fieldManager->getCodes() as $code => $labelKey)
			{
				$collection[$code] = new I18nString($i18n, $labelKey, array('ucf'));
			}

			$collection = new \Change\Collection\CollectionArray('Rbs_Simpleform_FieldTypes', $collection);
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}

	/**
	 * Available values for iOS-specific autocapitalize attribute.
	 * Cf. https://developer.apple.com/library/safari/documentation/AppleApplications/Reference/SafariHTMLRef/Articles/Attributes.html
	 * @param \Change\Events\Event $event
	 */
	public function addAutoCapitalizeOptions(\Change\Events\Event $event)
	{
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices instanceof \Change\Services\ApplicationServices)
		{
			$i18n = $applicationServices->getI18nManager();
			$collection = new \Change\Collection\CollectionArray('Rbs_Simpleform_AutoCapitalizeOptions', array(
				'none' => new I18nString($i18n, 'm.rbs.simpleform.admin.field_parameters_auto_capitalize_none', array('ucf')),
				'sentences' => new I18nString($i18n, 'm.rbs.simpleform.admin.field_parameters_auto_capitalize_sentences', array('ucf')),
				'words' => new I18nString($i18n, 'm.rbs.simpleform.admin.field_parameters_auto_capitalize_words', array('ucf')),
				'characters' => new I18nString($i18n, 'm.rbs.simpleform.admin.field_parameters_auto_capitalize_characters', array('ucf'))
			));
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}