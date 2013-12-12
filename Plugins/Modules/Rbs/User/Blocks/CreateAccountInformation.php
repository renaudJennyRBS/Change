<?php
namespace Rbs\User\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\User\Blocks\CreateAccountInformation
 */
class CreateAccountInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.user.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.user.admin.create_account', $ucf));
		$this->setFunctions(['Rbs_User_CreateAccount' => $i18nManager->trans('m.rbs.user.admin.function_create_account', $ucf)]);
	}
}
