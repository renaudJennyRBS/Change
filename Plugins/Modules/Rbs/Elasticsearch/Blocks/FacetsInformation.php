<?php
namespace Rbs\Elasticsearch\Blocks;

use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;
use Change\Presentation\Blocks\ParameterInformation;

/**
 * @name \Rbs\Elasticsearch\Blocks\FacetsInformation
 */
class FacetsInformation extends Information
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

		$this->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.facets'));

		$this->addInformationMeta('facetGroups', ParameterInformation::TYPE_DOCUMENTIDARRAY, true)
			->setLabel($i18nManager->trans('m.rbs.elasticsearch.blocks.facets-facetgroups', $ucf))
			->setAllowedModelsNames(array('Rbs_Elasticsearch_FacetGroup'));

		$this->addTTL(0)->setLabel($i18nManager->trans('m.rbs.admin.blocks.ttl', $ucf));
	}
}
