<?php
namespace Rbs\Payment\Blocks;

/**
 * @name \Rbs\Payment\Blocks\CreateAccountForOrderInformation
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
		$this->setSection($i18nManager->trans('m.rbs.payment.admin.module_name', $ucf));
		$this->setLabel($i18nManager->trans('m.rbs.payment.admin.create_account_for_transaction_label', $ucf));
	}
}