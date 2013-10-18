<?php
namespace Rbs\Event\Blocks;

use Change\Documents\Property;

/**
 * @name \Rbs\Event\Blocks\CategoryInformation
 */
class CategoryInformation extends \Rbs\Event\Blocks\Base\BaseEventListInformation
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
		$this->setLabel($i18nManager->trans('m.rbs.event.blocks.category-label'));
		$this->addInformationMeta('sectionRestriction', Property::TYPE_STRING, true, 'website')
			->setLabel($i18nManager->trans('m.rbs.event.blocks.category-section-restriction', $ucf))
			->setCollectionCode('Rbs_Event_Collection_SectionRestrictions');
		$this->getParameterInformation('templateName')->setDefaultValue('category.twig');
	}
}
