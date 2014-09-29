<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreFacetsInformation
 */
class StoreFacetsInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.store_facets_label', $ucf));

		$this->addInformationMetaForDetailBlock('Rbs_Catalog_ProductList', $i18nManager)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_list', $ucf));;

		$this->addInformationMeta('useCurrentSectionProductList', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_use_current', $ucf));

		$this->addInformationMeta('searchMode', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.search_mode', $ucf));

		$this->addInformationMeta('showUnavailable', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_show_unavailable', $ucf));

		$this->addInformationMeta('facets', \Change\Presentation\Blocks\ParameterInformation::TYPE_DOCUMENTIDARRAY)
			->setAllowedModelsNames('Rbs_Elasticsearch_Facet')
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.facets', $ucf));

		$this->addTTL(0);
	}
}
