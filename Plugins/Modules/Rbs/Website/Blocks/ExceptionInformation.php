<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Website\Blocks\ExceptionInformation
 */
class ExceptionInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.website.blocks.exception'));
		$this->addInformationMeta('showStackTrace', Property::TYPE_BOOLEAN, true, true)
			->setLabel($i18nManager->trans('m.rbs.website.blocks.exception-show-stack-trace', $ucf));
		$this->setFunctions(array('Error_500' => $i18nManager->trans('m.rbs.website.blocks.function-error-500', $ucf)));
	}
}