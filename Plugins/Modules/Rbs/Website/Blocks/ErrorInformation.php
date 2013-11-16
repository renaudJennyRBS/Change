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

		$this->setLabel($i18nManager->trans('m.rbs.website.admin.error', $ucf));
		$this->addInformationMeta('codeHttp', Property::TYPE_INTEGER, true, 404)
			->setLabel($i18nManager->trans('m.rbs.website.admin.error-codehttp', $ucf));
		$this->setFunctions(array(
			'Error_404' => $i18nManager->trans('m.rbs.website.admin.function_error_404', $ucf),
			'Error_403' => $i18nManager->trans('m.rbs.website.admin.function_error_403', $ucf)));
	}
}
