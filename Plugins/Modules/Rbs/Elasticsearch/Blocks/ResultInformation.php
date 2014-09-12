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
 * @name \Rbs\Elasticsearch\Blocks\ResultInformation
 */
class ResultInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result', $ucf));

		$this->addInformationMeta('itemsPerPage', Property::TYPE_INTEGER, false, 20)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result_items_per_page', $ucf));

		$this->addInformationMeta('showModelFacet', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result_show_model_facet', $ucf));

		$this->addInformationMeta('excludeProducts', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result_exclude_products', $ucf));

		$this->addTTL(0);
	}
}
