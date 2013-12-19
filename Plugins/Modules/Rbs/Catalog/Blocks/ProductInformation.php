<?php
namespace Rbs\Catalog\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductInformation
 */
class ProductInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.catalog.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_label', $ucf));
		$this->addInformationMeta('productId', Property::TYPE_DOCUMENT, false, null)
			->setAllowedModelsNames('Rbs_Catalog_Product')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_product', $ucf));
		$this->addInformationMeta('activateZoom', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_activate_zoom', $ucf));
		$this->addInformationMeta('attributesDisplayMode', Property::TYPE_STRING, false, 'table')
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_attributes_display_mode', $ucf));
		$this->setFunctions(array('Rbs_Catalog_Product' => $i18nManager->trans('m.rbs.catalog.admin.product_function', $ucf)));

		$this->addTTL(60)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
}
