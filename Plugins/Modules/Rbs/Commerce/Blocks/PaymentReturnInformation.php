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
 * @name \Rbs\Commerce\Blocks\PaymentReturnInformation
 */
class PaymentReturnInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.commerce.admin.payment_return_label', $ucf));
		$this->addInformationMeta('confirmationPage', Property::TYPE_DOCUMENTID, false, 0)
			->setAllowedModelsNames('Rbs_Website_StaticPage')
			->setLabel($i18nManager->trans('m.rbs.commerce.admin.confirmation_page', $ucf));
	}
}