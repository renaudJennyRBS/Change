<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductInformation
 */
class ProductInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.catalog.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Catalog_Product', $i18nManager);
		$this->addInformationMeta('activateZoom', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_activate_zoom', $ucf));
		$this->addInformationMeta('attributesDisplayMode', Property::TYPE_STRING, false, 'table')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_attributes_display_mode', $ucf))
			->setCollectionCode('Rbs_Catalog_SpecificationDisplayMode');

		$this->addTTL(60);
	}
}
