<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Blocks;

/**
 * @name \Rbs\Storeshipping\Blocks\ShortStoreInformation
 */
class ShortStoreInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.storeshipping.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.storeshipping.admin.short_store_label', $ucf));

		// Declare your parameters here.
		$this->addInformationMeta('autoSelect', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.storeshipping.admin.short_store_auto_select', $ucf));


	}
}