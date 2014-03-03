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
 * @name \Rbs\Elasticsearch\Blocks\FacetsInformation
 */
class FacetsInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.facets', $ucf));

		$model = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Catalog_ProductList');
		$allowedModelsNames = $model->getDescendantsNames();
		array_unshift($allowedModelsNames, $model->getName());
		$this->addInformationMeta('productListId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames($allowedModelsNames)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_list', $ucf));

		$this->addTTL(0)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
}
