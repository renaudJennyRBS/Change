<?php
namespace Rbs\Event\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\CategoryInformation
 */
class CategoryInformation extends \Rbs\Event\Blocks\Base\BaseEventListInformation
{
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
		$this->getParameterInformation('templateName')->setDefaultValue('category.twig');
	}
}
