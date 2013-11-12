<?php
namespace Rbs\Simpleform\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Simpleform\Blocks\FormInformation
 */
class FormInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.simpleform.blocks.form-label', $ucf));
		$this->addInformationMeta('formId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Simpleform_Form')
			->setLabel($i18nManager->trans('m.rbs.simpleform.blocks.form-form', $ucf));
		$this->setFunctions(array('Rbs_Simpleform_Form' => $i18nManager->trans('m.rbs.simpleform.blocks.form-function', $ucf)));
	}
}
