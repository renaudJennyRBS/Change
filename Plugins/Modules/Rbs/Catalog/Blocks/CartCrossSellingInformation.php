<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Catalog\Blocks\CartCrossSellingInformation
 */
class CartCrossSellingInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_cart_label', $ucf));
		$this->addInformationMeta('title', Property::TYPE_STRING, false, null)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_title', $ucf));
		$this->addInformationMeta('crossSellingType', ParameterInformation::TYPE_COLLECTION, true, 'ACCESSORIES')
			->setCollectionCode('Rbs_Catalog_Collection_CrossSellingType')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_type', $ucf));
		$this->addInformationMeta('productChoiceStrategy', ParameterInformation::TYPE_COLLECTION, true, 'LAST_PRODUCT')
			->setCollectionCode('Rbs_Catalog_CrossSelling_CartProductChoiceStrategy')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_product_choice_strategy', $ucf));
		$this->addInformationMeta('itemsPerSlide', Property::TYPE_INTEGER, true, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.cross_selling_items_per_slide', $ucf));
	}
}
