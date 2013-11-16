<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\ResultInformation
 */
class ResultInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');

		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.result', $ucf));
		$this->setFunctions(array(
				'Rbs_Elasticsearch_Result' => $i18nManager->trans('m.rbs.elasticsearch.blocks.result-function', $ucf))
		);

		$this->addInformationMeta('fulltextIndex', Property::TYPE_DOCUMENTID, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.result-fulltextindex', $ucf))
			->setAllowedModelsNames(array('Rbs_Elasticsearch_FullText'));
		$this->addTTL(0)->setLabel($i18nManager->trans('m.rbs.admin.admin.ttl', $ucf));
	}
}
