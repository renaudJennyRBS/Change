<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\ResultInformation
 */
class ResultInformation extends Information
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

		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.result'));

		$this->setFunctions(array(
			'Rbs_Elasticsearch_Result' => $i18nManager->trans('m.rbs.elasticsearch.blocks.result-function'))
		);
		$this->addInformationMeta('autoComplete', Property::TYPE_BOOLEAN, false, false)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.result-autocomplete', $ucf));

		$this->addInformationMeta('fulltextIndex', Property::TYPE_DOCUMENTID, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.result-fulltextindex', $ucf))
			->setAllowedModelsNames(array('Rbs_Elasticsearch_FullText'));
	}
}
