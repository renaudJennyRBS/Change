<?php
namespace Rbs\Commerce\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Commerce\Http\Web\CreateAccountConfirmation
*/
class CreateAccountConfirmation extends \Rbs\User\Http\Web\CreateAccountConfirmation
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @param $email
	 * @param $params
	 * @throws \Exception
	 * @return \Rbs\User\Documents\User
	 */
	public function createUser(\Change\Http\Web\Event $event, $email, $params)
	{
		$user = parent::createUser($event, $email, $params);

		$key = 'Rbs_Commerce_TransactionId';
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof \Rbs\Commerce\CommerceServices && isset($params[$key]))
		{
			$transaction = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($params[$key]);
			if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
			{
				$commerceServices->getProcessManager()->handleRegistrationForTransaction($user, $transaction);
			}
		}

		return $user;
	}
}