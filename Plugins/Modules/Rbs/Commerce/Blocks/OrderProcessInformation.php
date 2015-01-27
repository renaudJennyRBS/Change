<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Commerce\Blocks\OrderProcessInformation
 */
class OrderProcessInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.commerce.admin.order_process_label', $ucf));
		$this->addParameterInformation('realm', Property::TYPE_STRING, true, 'web')
			->setLabel($i18nManager->trans('m.rbs.user.admin.login_realm', $ucf));
	}
}