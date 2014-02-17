<?php
namespace Rbs\Brand\Blocks;

use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Catalog\Blocks\ProductInformation
 */
class BrandInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.brand.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.brand.admin.brand', $ucf));
		$this->addInformationMetaForDetailBlock('Rbs_Brand_Brand', $i18nManager);

		$this->addTTL(60)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
}
