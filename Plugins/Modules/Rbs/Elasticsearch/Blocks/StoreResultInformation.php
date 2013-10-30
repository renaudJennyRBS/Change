<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\StoreResultInformation
 */
class StoreResultInformation extends Information
{
	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$ucf = array('ucf');

		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();

		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.storeresult'));

		$this->setFunctions(array(
			'Rbs_Elasticsearch_StoreResult' => $i18nManager->trans('m.rbs.elasticsearch.blocks.storeresult-function'))
		);

		$this->addInformationMeta('storeIndex', Property::TYPE_DOCUMENTID, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.storeresult-storeindex', $ucf))
			->setAllowedModelsNames(array('Rbs_Elasticsearch_StoreIndex'));

		$this->addTTL(0)->setLabel($i18nManager->trans('m.rbs.admin.blocks.ttl', $ucf));
	}
}
