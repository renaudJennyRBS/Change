<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Store\Blocks;

use Change\Presentation\Blocks\Information;
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Store\Blocks\WebStoreSelectorInformation
 */
class WebStoreSelectorInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.store.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.store.admin.web_store_selector_label', $ucf));
		$this->addInformationMeta('availableWebStoreIds', ParameterInformation::TYPE_DOCUMENTIDARRAY, true, array())
			->setAllowedModelsNames('Rbs_Store_WebStore')
			->setLabel($i18nManager->trans('m.rbs.store.admin.web_store_selector_available_web_stores', $ucf));

		$templateInformation = $this->addTemplateInformation('Rbs_Store', 'webStoreSelector-vertical.twig');
		$templateInformation->setLabel($i18nManager->trans('m.rbs.store.admin.template_vertical_label', ['ucf']));
	}
}