<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Blocks;

/**
 * @name \Rbs\User\Blocks\AccountShortInformation
 */
class AccountShortInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.user.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.user.admin.account_short', $ucf));
		$this->addParameterInformationForDetailBlock(array('Rbs_Website_Topic', 'Rbs_Website_Website', 'Rbs_Website_Menu'), $i18nManager);

		$this->addParameterInformation('userAccountPage', \Change\Documents\Property::TYPE_DOCUMENTID, false, null)
			->setLabel($i18nManager->trans('m.rbs.user.admin.user_account_page', array('ucf')))
			->setAllowedModelsNames(array('Rbs_Website_Topic', 'Rbs_Website_StaticPage'));
	}
}
