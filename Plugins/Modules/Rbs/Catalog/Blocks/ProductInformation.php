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
		$this->setLabel($i18nManager->trans('m.rbs.catalog.blocks.product-label', $ucf));
		$this->addInformationMeta('productId', Property::TYPE_DOCUMENT, false, null)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.product-product', $ucf));
		$this->addInformationMeta('activateZoom', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.product-activate-zoom', $ucf));
		$this->addInformationMeta('attributesDisplayMode', Property::TYPE_STRING, false, 'table')
			->setLabel($i18nManager->trans('m.rbs.catalog.blocks.product-attributes-display-mode', $ucf));
		$this->setFunctions(array('Rbs_Catalog_Product' => $i18nManager->trans('m.rbs.catalog.blocks.product-function', $ucf)));

		$this->addTTL(60)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
}
