<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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
		$this->addInformationMeta('confirmationPage', Property::TYPE_DOCUMENTID, false, 0)
			->setAllowedModelsNames('Rbs_Website_StaticPage')
			->setLabel($i18nManager->trans('m.rbs.user.admin.confirmation_page', $ucf));
		$this->addTTL(0);
	}
}
