<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Storelocator\Blocks\StoreInformation
 */
class StoreInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.storelocator.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.storelocator.admin.store_label', $ucf));
		$this->addParameterInformationForDetailBlock('Rbs_Storelocator_Store', $i18nManager);

		$this->addParameterInformation('showChooseStore', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.storelocator.admin.show_choose_store', $ucf));
	}
}
