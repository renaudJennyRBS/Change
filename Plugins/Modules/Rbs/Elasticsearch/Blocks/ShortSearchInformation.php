<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\ShortSearchInformation
 */
class ShortSearchInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.elasticsearch.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.shortsearch', $ucf));
		$this->addInformationMeta('resultSectionId', Property::TYPE_DOCUMENT)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.admin.shortsearch_resultsectionid', $ucf))
			->setAllowedModelsNames(array('Rbs_Website_Topic', 'Rbs_Website_Website'));

		$this->addTTL(3600, $i18nManager->trans('"m.rbs.admin.admin.ttl', $ucf));
	}
}
