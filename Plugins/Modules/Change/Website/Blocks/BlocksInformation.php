<?php
namespace Change\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;

/**
 * @name \Change\Website\Blocks\BlocksInformation
 */
class BlocksInformation extends \Change\Presentation\Blocks\Information
{
	/**
	 * @param string $name
	 * @param BlockManager $blockManager
	 */
	function __construct($name, $blockManager)
	{
		parent::__construct($name);
		$i18nManager = $blockManager->getPresentationServices()->getApplicationServices()->getI18nManager();
		if ($name === 'Change_Website_Richtext')
		{
			$this->setLabel($i18nManager->trans('m.change.website.blocks.richtext'));
			$this->addInformationMeta('contentType', Property::TYPE_STRING, true, 'bbcode');
		}
		elseif ($name === 'Change_Website_Menu')
		{
			$this->setLabel($i18nManager->trans('m.change.website.blocks.menu'));
			$this->addInformationMeta('documentId', Property::TYPE_DOCUMENT)->setAllowedModelsNames('Change_Website_Section');
			$this->addInformationMeta('maxLevel', Property::TYPE_INTEGER, true, 1);
		}
	}
}