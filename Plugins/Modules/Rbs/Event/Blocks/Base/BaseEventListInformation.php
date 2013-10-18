<?php
namespace Rbs\Event\Blocks\Base;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\Base\BaseEventListInformation
 */
abstract class BaseEventListInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param string $name
	 * @param \Change\Presentation\Blocks\BlockManager $blockManager
	 */
	public function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->addInformationMeta('showTime', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-show-time', $ucf));
		$this->addInformationMeta('contextualUrls', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-contextual-urls', $ucf));
		$this->addInformationMeta('showCategories', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-show-categories', $ucf));
		$this->addInformationMeta('contextualCategoryUrls', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-contextual-category-urls', $ucf));
		$this->addInformationMeta('itemsPerPage', Property::TYPE_INTEGER, true, 10)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-items-per-page', $ucf));
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-template-name', $ucf));
	}
}
