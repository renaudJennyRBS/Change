<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;

/**
 * @name \Rbs\Website\Blocks\SiteMapInformation
 */
class SiteMapInformation extends MenuInformation
{
	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name, $blockManager);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.website.blocks.sitemap', $ucf));
		$this->getParameterInformation('templateName')->setDefaultValue('siteMap.twig');
		$this->getParameterInformation('maxLevel')->setDefaultValue(5);
		$this->removeParameterInformation('documentId');
	}
}