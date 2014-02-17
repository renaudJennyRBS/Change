<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Website\Blocks\ErrorInformation
 */
class ErrorInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.error', $ucf));
		$this->addInformationMeta('codeHttp', Property::TYPE_INTEGER, true, 404)
			->setLabel($i18nManager->trans('m.rbs.website.admin.error_codehttp', $ucf));
	}
}
