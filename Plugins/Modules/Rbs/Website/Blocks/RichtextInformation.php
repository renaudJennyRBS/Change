<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;

class RichtextInformation  extends \Change\Presentation\Blocks\Information
{

	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		$this->setLabel($i18nManager->trans('m.rbs.website.blocks.richtext'));
		$this->addInformationMeta('contentType', Property::TYPE_STRING, true, 'html');
	}
}