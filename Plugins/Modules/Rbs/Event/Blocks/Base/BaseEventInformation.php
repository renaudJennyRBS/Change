<?php
namespace Rbs\Event\Blocks\Base;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\Base\BaseEventInformation
 */
abstract class BaseEventInformation extends \Change\Presentation\Blocks\Information
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
		$this->addInformationMeta('docId', Property::TYPE_DOCUMENTID, false, null); // Label ans allowed model should be set in final class.
		$this->addInformationMeta('showTime', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-show-time', $ucf));
		$this->addInformationMeta('showCategories', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-show-categories', $ucf));
		$this->addInformationMeta('contextualUrls', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-contextual-urls', $ucf));
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false) // Default value should be set in final class.
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-template-name', $ucf));
	}
}
