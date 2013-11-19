<?php
namespace Rbs\Event\Blocks\Base;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\Base\BaseEventListInformation
 */
abstract class BaseEventListInformation extends \Change\Presentation\Blocks\Information
{
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
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_template_name', $ucf));
	}
}
