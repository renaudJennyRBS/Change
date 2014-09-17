<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

/**
 * @name \Rbs\Website\Blocks\TrackersManageInformation
 */
class TrackersManageInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_manage_label', $ucf));

		$this->addInformationMeta('notChosenText', \Change\Documents\Property::TYPE_STRING)
			->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_manage_not_chosen_text', $ucf));
		$this->addInformationMeta('allowedText', \Change\Documents\Property::TYPE_STRING)
			->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_manage_allowed_text', $ucf));
		$this->addInformationMeta('notAllowedText', \Change\Documents\Property::TYPE_STRING)
			->setLabel($i18nManager->trans('m.rbs.website.admin.trackers_manage_not_allowed_text', $ucf));
	}
}