<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Brand\Blocks;

use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductInformation
 */
class BrandInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.brand.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.brand.admin.brand', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Brand_Brand', $i18nManager);

		$this->addTTL(60)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
}
