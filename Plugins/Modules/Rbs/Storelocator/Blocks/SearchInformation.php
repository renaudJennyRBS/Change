<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storelocator\Blocks;

use Change\Documents\Property;
/**
 * @name \Rbs\Storelocator\Blocks\SearchInformation
 */
class SearchInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);

		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.storelocator.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.storelocator.admin.search_label', $ucf));

		$this->addInformationMeta('commercialSignId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Storelocator_CommercialSign')
			->setLabel($i18nManager->trans('m.rbs.storelocator.admin.commercial_sign', $ucf));

		$this->addInformationMeta('showChooseStore', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.storelocator.admin.show_choose_store', $ucf));

		$this->addInformationMeta('backgroundEmptyImage', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Media_Image')
			->setLabel($i18nManager->trans('m.rbs.storelocator.admin.background_empty_image', $ucf));

		$this->addInformationMeta('facet', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Elasticsearch_Facet')
			->setLabel($i18nManager->trans('m.rbs.storelocator.admin.search_facet', $ucf));
	}
}
