<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Theme\Blocks;

/**
 * @name \Rbs\Theme\Blocks\ThemeSelectorMailInformation
 */
class ThemeSelectorMailInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.theme.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.theme.admin.theme_selector_label', $ucf));
		$this->setMailSuitable(true);
		$this->addTTL(3600);
	}
}