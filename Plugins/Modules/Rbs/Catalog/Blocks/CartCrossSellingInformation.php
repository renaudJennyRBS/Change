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
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Catalog\Blocks\CartCrossSellingInformation
 */
class CartCrossSellingInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.catalog.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_cart_label', $ucf));
		$this->addInformationMeta('title', Property::TYPE_STRING, false, null)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_title', $ucf));
		$this->addInformationMeta('crossSellingType', ParameterInformation::TYPE_COLLECTION, true, 'ACCESSORIES')
			->setCollectionCode('Rbs_Catalog_Collection_CrossSellingType')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_type', $ucf));
		$this->addInformationMeta('productChoiceStrategy', ParameterInformation::TYPE_COLLECTION, true, 'LAST_PRODUCT')
			->setCollectionCode('Rbs_Catalog_CrossSelling_CartProductChoiceStrategy')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_product_choice_strategy', $ucf));
		$this->addInformationMeta('itemsPerSlide', Property::TYPE_INTEGER, true, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_items_per_slide', $ucf));
		$this->addInformationMeta('interval', \Change\Documents\Property::TYPE_INTEGER, 1000)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_slider_interval', ['ucf']))
			->setNormalizeCallback(function ($parametersValues) {
				$value = isset($parametersValues['interval']) ? intval($parametersValues['interval']) : 1000;
				return ($value <= 500) ? 500 : $value;});
	}
}
