<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductInformation
 */
class ProductInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.catalog.blocks.product-label'));
		$this->addInformationMeta('productId', Property::TYPE_INTEGER, false, null)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.product-product', $ucf));
		$this->setFunctions(array('Rbs_Catalog_Product' => $i18nManager->trans('m.rbs.catalog.blocks.product-function', $ucf)));
	}
}
