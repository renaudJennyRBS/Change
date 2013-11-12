<?php
namespace Rbs\Simpleform\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Simpleform\Blocks\FormInformation
 */
class FormInformation extends Information
{
	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	public function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.simpleform.blocks.form-label'));
		$this->addInformationMeta('formId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Simpleform_Form')
			->setLabel($i18nManager->trans('m.rbs.simpleform.blocks.form-form', $ucf));
		$this->setFunctions(array('Rbs_Simpleform_Form' => $i18nManager->trans('m.rbs.simpleform.blocks.form-function', $ucf)));
	}
}
