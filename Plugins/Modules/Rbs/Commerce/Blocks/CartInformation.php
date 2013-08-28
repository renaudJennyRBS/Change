<?php
namespace Rbs\Commerce\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\BlockManager;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Commerce\Blocks\CartInformation
 */
class CartInformation extends Information
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
		$this->setLabel($i18nManager->trans('m.rbs.commerce.blocks.cart-label'));
		$this->setFunctions(array('Rbs_Commerce_Cart' => $i18nManager->trans('m.rbs.commerce.blocks.cart-function', $ucf)));
	}
}
