<?php
namespace Rbs\Store\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Store\Blocks\WebStoreSelectorInformation
 */
class WebStoreSelectorInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.store.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.store.admin.web_store_selector_label', $ucf));
		$this->addInformationMeta('availableWebStoreIds', ParameterInformation::TYPE_DOCUMENTIDARRAY, true, array())
			->setAllowedModelsNames('Rbs_Store_WebStore')
			->setLabel($i18nManager->trans('m.rbs.store.admin.web_store_selector_available_web_stores', $ucf));
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false, 'webStoreSelector-horizontal.twig')
			->setLabel($i18nManager->trans('m.rbs.generic.admin.block_template_name', $ucf));
	}
}