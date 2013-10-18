<?php
namespace Rbs\Event\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\ContextualListInformation
 */
class ContextualListInformation extends \Rbs\Event\Blocks\Base\BaseEventListInformation
{
	/**
	 * @param string $name
	 * @param \Change\Presentation\Blocks\BlockManager $blockManager
	 */
	public function __construct($name, $blockManager)
	{
		parent::__construct($name, $blockManager);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.event.blocks.contextual-list-label'));
		$this->addInformationMeta('sectionId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames('Rbs_Website_Section')
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-section-id', $ucf));
		$this->addInformationMeta('includeSubSections', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.event.blocks.base-event-list-include-sub-sections', $ucf));
		$this->getParameterInformation('templateName')->setDefaultValue('contextual-list.twig');
	}
}
