<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Payment\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Payment\Http\Web\CreateAccountConfirmation
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
				$tm = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();

					$transaction->setAuthorId($user->getId());
					if (!$transaction->getOwnerId())
					{
						$transaction->setOwnerId($user->getId());
					}
					$transaction->save();

					$commerceServices->getPaymentManager()->handleRegistrationForTransaction($user, $transaction);

					$tm->commit();
				}
				catch (\Exception $e)
				{
					$tm->rollBack($e);
				}
			}
		}

		return $user;
	}
}