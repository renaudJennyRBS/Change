<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Blocks;

/**
 * @name \Rbs\Payment\Blocks\CreateAccountForOrderInformation
 */
class CreateAccountForTransactionInformation extends \Rbs\User\Blocks\CreateAccountInformation
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.payment.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.payment.admin.create_account_for_transaction_label', $ucf));
	}
}