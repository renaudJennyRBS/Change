<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;

/**
 * @name \Rbs\Website\Blocks\XhtmlTemplateInformation
 */
class XhtmlTemplateInformation  extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param string $name
	 * @param \Change\Presentation\Blocks\BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$ucf = array('ucf');
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans($i18nManager->trans('m.rbs.website.blocks.xhtml-template', $ucf)));
		$this->addInformationMeta('moduleName', Property::TYPE_STRING, true, 'Rbs_Website');
		$this->addInformationMeta('templateName', Property::TYPE_STRING, false);
	}
}