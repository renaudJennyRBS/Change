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

		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.storeresult', $ucf));

		$this->setFunctions(array(
				'Rbs_Elasticsearch_StoreResult' => $i18nManager->trans('m.rbs.elasticsearch.blocks.storeresult-function', $ucf))
		);

		$this->addInformationMeta('storeIndex', Property::TYPE_DOCUMENTID, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.storeresult-storeindex', $ucf))
			->setAllowedModelsNames(array('Rbs_Elasticsearch_StoreIndex'));

		$this->addTTL(0)->setLabel($i18nManager->trans('m.rbs.admin.blocks.ttl', $ucf));
	}
}
