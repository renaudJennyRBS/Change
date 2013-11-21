<?php
namespace Rbs\Website\Blocks;

/**
 * @name \Rbs\Website\Blocks\SwitchLangInformation
 */
class SwitchLangInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.switchlang', $ucf));
		$this->setFunctions(array('Rbs_Website_Website_SwitchLang' => $i18nManager->trans('m.rbs.website.admin.switchlang', $ucf)));
	}
}