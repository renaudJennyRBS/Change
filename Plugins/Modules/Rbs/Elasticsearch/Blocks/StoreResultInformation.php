<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreResultInformation
 */
class StoreResultInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.storeresult', $ucf));

		$this->setFunctions(array(
				'Rbs_Elasticsearch_StoreResult' => $i18nManager->trans('m.rbs.elasticsearch.admin.storeresult_function', $ucf))
		);

		$this->addInformationMeta('storeIndex', Property::TYPE_DOCUMENTID, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.storeresult_storeindex', $ucf))
			->setAllowedModelsNames(array('Rbs_Elasticsearch_StoreIndex'));

		$model = $event->getApplicationServices()->getModelManager()->getModelByName('Rbs_Catalog_ProductList');
		$allowedModelsNames = $model->getDescendantsNames();
		array_unshift($allowedModelsNames, $model->getName());
		$this->addInformationMeta('productListId', Property::TYPE_DOCUMENTID, false, null)
			->setAllowedModelsNames($allowedModelsNames)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_list', $ucf));

		$this->addTTL(0)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));

		$this->addInformationMeta('contextualUrls', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_contextual_urls', $ucf));

		$this->addInformationMeta('itemsPerLine', Property::TYPE_INTEGER, true, 3)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_items_per_line', $ucf));

		$this->addInformationMeta('itemsPerPage', Property::TYPE_INTEGER, true, 9)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_items_per_page', $ucf));

		$this->addInformationMeta('showOrdering', Property::TYPE_BOOLEAN, false, true)
			->setLabel($i18nManager->trans('m.rbs.catalog.admin.product_list_show_ordering', $ucf));
	}
}
