<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Event\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\ContextualListInformation
 */
class ContextualListInformation extends \Rbs\Event\Blocks\Base\BaseEventListInformation
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = ['ucf'];
		$this->setLabel($i18nManager->trans('m.rbs.event.admin.contextual_list_label', $ucf));
		$this->addParameterInformation('sectionId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Website_Section')
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_section_id', $ucf));
		$this->addParameterInformation('includeSubSections', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_include_sub_sections', $ucf));
	}
}
