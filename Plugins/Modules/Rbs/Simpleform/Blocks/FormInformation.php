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
		$this->setSection($i18nManager->trans('m.rbs.simpleform.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.simpleform.admin.block_form_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Simpleform_Form', $i18nManager);
		$this->addTTL(0);
	}
}
