<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * Class MenuInformation
 * @package Rbs\Website\Blocks
 */
class MenuInformation extends Information
{
	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.website.blocks.menu', $ucf));
		$this->addInformationMeta('templateName', Property::TYPE_STRING, true, 'menu.twig')
			->setLabel($i18nManager->trans('m.rbs.website.blocks.menu-templatename', $ucf));
		$this->addInformationMeta('documentId', Property::TYPE_DOCUMENT)->setAllowedModelsNames(array('Rbs_Website_Section',
			'Rbs_Website_Menu'))->setLabel($i18nManager->trans('m.rbs.website.blocks.menu-documentid', $ucf));
		$this->addInformationMeta('maxLevel', Property::TYPE_INTEGER, true, 1)
			->setLabel($i18nManager->trans('m.rbs.website.blocks.menu-maxlevel', $ucf));
		$this->addInformationMeta('showTitle', Property::TYPE_BOOLEAN, true, false)
			->setLabel($i18nManager->trans('m.rbs.website.blocks.menu-showtitle', $ucf));
	}
}
