<?php
namespace Rbs\Catalog\Blocks;

/**
 * @name \Rbs\Catalog\Blocks\ProductAddedToCartInformation
 */
class ProductAddedToCartInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.catalog.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_added_to_cart_label', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Catalog_Product', $i18nManager);

		$this->addTTL(60)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
} 