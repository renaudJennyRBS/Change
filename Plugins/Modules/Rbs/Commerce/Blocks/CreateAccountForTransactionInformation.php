<?php
namespace Rbs\Commerce\Blocks;

/**
 * @name \Rbs\Commerce\Blocks\CreateAccountForOrderInformation
 */
class CreateAccountForTransactionInformation extends \Rbs\User\Blocks\CreateAccountInformation
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
		$this->setLabel($i18nManager->trans('m.rbs.commerce.admin.create_account_for_transaction_label', $ucf));
	}
}