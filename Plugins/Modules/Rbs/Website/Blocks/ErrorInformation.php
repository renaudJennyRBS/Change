<?php
namespace Rbs\Website\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Website\Blocks\ErrorInformation
 */
class ErrorInformation extends Information
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

		$this->setLabel($i18nManager->trans('m.rbs.website.blocks.error'));
		$this->addInformationMeta('codeHttp', Property::TYPE_INTEGER, true, 404)
			->setLabel($i18nManager->trans('m.rbs.website.blocks.error-codehttp', $ucf));
		$this->setFunctions(array(
			'Error_404' => $i18nManager->trans('m.rbs.website.blocks.function-error-404', $ucf),
			'Error_403' => $i18nManager->trans('m.rbs.website.blocks.function-error-403', $ucf)));
	}
}
