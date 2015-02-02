<?php
/**
 * Copyright (C) 2014 Ready Business System, Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductListInformation
 */
class ProductListInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.catalog.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_label', $ucf));
		$this->addParameterInformationForDetailBlock('Rbs_Catalog_ProductList', $i18nManager)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_list', $ucf));

		$this->addParameterInformation('useCurrentSectionProductList', \Change\Documents\Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_use_current', $ucf));
		$this->addParameterInformation('contextualUrls', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_contextual_urls', $ucf));
		$this->addParameterInformation('itemsPerPage', \Change\Documents\Property::TYPE_INTEGER, false, 9)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_items_per_page', $ucf));
		$this->addParameterInformation('showUnavailable', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_show_unavailable', $ucf));
		$this->addParameterInformation('quickBuyOnSimple', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_quick_buy_on_simple', $ucf));
		$this->addParameterInformation('quickBuyOnVariant', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_quick_buy_on_variant', $ucf));

		$templateInformation = $this->addDefaultTemplateInformation();
		$templateInformation->addParameterInformation('itemsPerLine', \Change\Documents\Property::TYPE_INTEGER, false, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_items_per_line', $ucf));
		$templateInformation->addParameterInformation('showOrdering', \Change\Documents\Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_show_ordering', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Catalog', 'product-list-infinite-scroll.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_infinite_scroll_label', ['ucf']));
		$templateInformation->addParameterInformation('itemsPerLine', \Change\Documents\Property::TYPE_INTEGER, false, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_items_per_line', $ucf));
	}
}