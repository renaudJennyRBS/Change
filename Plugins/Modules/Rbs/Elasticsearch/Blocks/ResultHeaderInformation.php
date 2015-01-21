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
 * @name \Rbs\Elasticsearch\Blocks\ResultHeaderInformation
 */
class ResultHeaderInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result_header_label', $ucf));

		$this->addParameterInformation('productResultsPage', \Change\Documents\Property::TYPE_DOCUMENT)
			->setAllowedModelsNames('Rbs_Website_StaticPage')
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result_header_product_results_page', $ucf));
		$this->addParameterInformation('otherResultsPage', \Change\Documents\Property::TYPE_DOCUMENT)
			->setAllowedModelsNames('Rbs_Website_StaticPage')
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.result_header_other_results_page', $ucf));
	}
}