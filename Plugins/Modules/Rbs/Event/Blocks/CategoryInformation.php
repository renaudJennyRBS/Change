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
 * @name \Rbs\Event\Blocks\CategoryInformation
 */
class CategoryInformation extends \Rbs\Event\Blocks\Base\BaseEventListInformation
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.event.admin.category_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Event_Category', $i18nManager);
		$this->addInformationMeta('sectionRestriction', Property::TYPE_STRING, true, 'website')
			->setLabel($i18nManager->trans('m.rbs.event.admin.category_section_restriction', $ucf))
			->setCollectionCode('Rbs_Event_Collection_SectionRestrictions');
	}
}
