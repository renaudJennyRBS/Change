<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Website\Blocks\ExceptionInformation
 */
class ExceptionInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.exception', $ucf));
		$this->addInformationMeta('showStackTrace', Property::TYPE_BOOLEAN, true, true)
			->setLabel($i18nManager->trans('m.rbs.website.admin.exception_show_stack_trace', $ucf));
		$this->setFunctions(array('Error_500' => $i18nManager->trans('m.rbs.website.admin.function_error_500', $ucf)));
	}
}