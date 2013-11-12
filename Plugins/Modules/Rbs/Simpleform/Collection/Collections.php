<?php
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
				'message' => new I18nString($i18n, 'm.rbs.simpleform.documents.form.confirmationmode-message', array('ucf')),
				'popin' => new I18nString($i18n, 'm.rbs.simpleform.documents.form.confirmationmode-popin', array('ucf')),
				'page' => new I18nString($i18n, 'm.rbs.simpleform.documents.form.confirmationmode-page', array('ucf'))
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
				'none' => new I18nString($i18n, 'm.rbs.simpleform.documents.field.parameters-auto-capitalize-none', array('ucf')),
				'sentences' => new I18nString($i18n, 'm.rbs.simpleform.documents.field.parameters-auto-capitalize-sentences', array('ucf')),
				'words' => new I18nString($i18n, 'm.rbs.simpleform.documents.field.parameters-auto-capitalize-words', array('ucf')),
				'characters' => new I18nString($i18n, 'm.rbs.simpleform.documents.field.parameters-auto-capitalize-characters', array('ucf'))
			));
			$event->setParam('collection', $collection);
			$event->stopPropagation();
		}
	}
}