<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Website\Blocks\XhtmlTemplateInformation
 */
class XhtmlTemplateInformation extends \Change\Presentation\Blocks\Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.website.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans($i18nManager->trans('m.rbs.website.admin.xhtml_template', $ucf)));
		$this->addInformationMeta('moduleName', Property::TYPE_STRING, true, 'Rbs_Website')
			->setLabel($i18nManager->trans('m.rbs.website.admin.block_module_name', $ucf));
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false)
			->setLabel($i18nManager->trans('m.rbs.website.admin.block_template_name', $ucf));
	}
}