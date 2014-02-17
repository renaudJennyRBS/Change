<?php
namespace Rbs\Event\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\ContextualListInformation
 */
class ContextualListInformation extends \Rbs\Event\Blocks\Base\BaseEventListInformation
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.event.admin.contextual_list_label', $ucf));
		$this->addInformationMeta('sectionId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Website_Section')
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_section_id', $ucf));
		$this->addInformationMeta('includeSubSections', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.admin.base_event_list_include_sub_sections', $ucf));
		$this->getParameterInformation('templateName')->setDefaultValue('contextual-list.twig');
	}
}
