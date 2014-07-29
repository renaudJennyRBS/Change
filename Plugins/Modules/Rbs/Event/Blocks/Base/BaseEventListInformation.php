<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Event\Blocks\Base;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\Base\BaseEventListInformation
 */
abstract class BaseEventListInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.event.admin.module_name', $ucf));
		$this->addInformationMeta('showTime', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_show_time', $ucf));
		$this->addInformationMeta('contextualUrls', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_contextual_urls', $ucf));
		$this->addInformationMeta('showCategories', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_show_categories', $ucf));
		$this->addInformationMeta('contextualCategoryUrls', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_contextual_category_urls', $ucf));
		$this->addInformationMeta('itemsPerPage', Property::TYPE_INTEGER, true, 10)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_items_per_page', $ucf));
	}
}
