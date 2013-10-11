<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Catalog\Blocks\CartCrossSellingInformation
 */
class CartCrossSellingInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.catalog.blocks.cross-selling-cart-label'));
		$this->addInformationMeta('title', Property::TYPE_STRING, false, null)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.cross-selling-title', $ucf));
		$this->addInformationMeta('crossSellingType', ParameterInformation::TYPE_COLLECTION, true, 'ACCESSORIES')
			->setCollectionCode('Rbs_Catalog_Collection_CrossSellingType')
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.cross-selling-type', $ucf));
		/*$this->addInformationMeta('crossSellingType', ParameterInformation::TYPE_COLLECTION, true, 'ACCESSORIES')
			->setCollectionCode('Rbs_Catalog_Collection_CrossSellingType')
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.cross-selling-type', $ucf));*/
		$this->addInformationMeta('productChoiceStrategy', Property::TYPE_STRING, true, 'LAST_PRODUCT')
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.cross-selling-type', $ucf));
		$this->addInformationMeta('itemsPerSlide', Property::TYPE_INTEGER, true, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.cross-selling-items-per-slide', $ucf));
	}
}
