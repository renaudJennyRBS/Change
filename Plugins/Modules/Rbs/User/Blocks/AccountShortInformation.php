<?php
namespace Rbs\User\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\User\Blocks\AccountShortInformation
 */
class AccountShortInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.user.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.user.admin.account_short', $ucf));
	}
}
