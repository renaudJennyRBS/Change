<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Simpleform\Blocks;

use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Simpleform\Blocks\FormInformation
 */
class FormInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.simpleform.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.simpleform.admin.block_form_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Simpleform_Form', $i18nManager);
		$this->addTTL(0);
	}
}
