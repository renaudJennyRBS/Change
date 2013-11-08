<?php
namespace Rbs\Commerce\Blocks;

use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Commerce\Blocks\CartInformation
 */
class ShortCartInformation extends Information
{
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setLabel($i18nManager->trans('m.rbs.commerce.blocks.shortcart-label', $ucf));
		$this->setFunctions(array('Rbs_Commerce_Cart' => $i18nManager->trans('m.rbs.commerce.blocks.cart-function', $ucf)));
	}
}
