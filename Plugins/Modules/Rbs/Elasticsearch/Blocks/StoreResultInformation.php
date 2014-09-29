<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Elasticsearch\Blocks;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreResultInformation
 */
class StoreResultInformation extends \Rbs\Catalog\Blocks\ProductListInformation
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.storeresult', $ucf));

		$this->getParameterInformation('useCurrentSectionProductList')->setHidden(true)
			->setDefaultValue(false)->setLabel(null);

		$this->getParameterInformation('contextualUrls')->setHidden(true)
			->setDefaultValue(false)->setLabel(null);
	}
}
