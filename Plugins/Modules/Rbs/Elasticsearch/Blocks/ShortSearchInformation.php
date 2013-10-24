<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Elasticsearch\Blocks\ShortSearchInformation
 */
class ShortSearchInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.shortsearch'));
		$this->addInformationMeta('resultSectionId', Property::TYPE_DOCUMENT)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.shortsearch-resultsectionid', $ucf))
			->setAllowedModelsNames(array('Rbs_Website_Topic', 'Rbs_Website_Website'));

		$this->addTTL(3600, $i18nManager->trans('"m.rbs.admin.blocks.ttl', $ucf));
	}
}
