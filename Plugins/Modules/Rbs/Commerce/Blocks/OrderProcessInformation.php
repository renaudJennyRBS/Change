<?php
namespace Rbs\Commerce\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Information;

/**
 * @name \Rbs\Commerce\Blocks\OrderProcessInformation
 */
class OrderProcessInformation extends Information
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function onInformation(\Change\Events\Event $event)
	{
		parent::onInformation($event);
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$ucf = array('ucf');
		$this->setSection($i18nManager->trans('m.rbs.commerce.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.commerce.admin.order_process_label', $ucf));
		$this->addInformationMeta('realm', Property::TYPE_STRING, true, 'web')
			->setLabel($i18nManager->trans('m.rbs.user.admin.login_realm', $ucf));
	}
}