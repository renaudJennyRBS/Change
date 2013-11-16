<?php
namespace Rbs\User\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\User\Blocks\LoginInformation
 */
class LoginInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.user.admin.login', $ucf));
		$this->addInformationMeta('realm', Property::TYPE_STRING, true, 'web')
			->setLabel($i18nManager->trans('m.rbs.user.admin.login-realm', $ucf));
		$this->setFunctions(array('Error_401' => $i18nManager->trans('m.rbs.user.admin.function_error_401', $ucf)));
	}
}
