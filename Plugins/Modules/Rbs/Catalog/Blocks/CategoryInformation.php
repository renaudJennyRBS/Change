<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\CategoryInformation
 */
class CategoryInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.catalog.blocks.productlist-label'));
		$this->addInformationMeta('categoryId', Property::TYPE_INTEGER, false, null)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.productlist-category', $ucf));
		$this->addInformationMeta('itemsPerLine', Property::TYPE_INTEGER, true, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.productlist-items-per-line', $ucf));
		$this->addInformationMeta('itemsPerPage', Property::TYPE_INTEGER, true, 9)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.productlist-items-per-page', $ucf));
		$this->setFunctions(array('Rbs_Catalog_Category' => $i18nManager->trans('m.rbs.catalog.blocks.productlist-function', $ucf)));
	}
}
