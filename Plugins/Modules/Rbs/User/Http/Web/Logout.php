<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\Logout
*/
class Logout extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @param Event $event
	 * @return mixed|void
	 */
	public function execute(Event $event)
	{
		$this->logout($event);
	}

	/**
	 * @param Event $event
	 */
	public function logout(Event $event)
	{
		$cookie = $event->getRequest()->getCookie();

		$authenticationManager = $event->getAuthenticationManager();
		$transactionManager = $event->getApplicationServices()->getTransactionManager();
		$dbProvider = $event->getApplicationServices()->getDbProvider();

		$currentUser = $authenticationManager->getCurrentUser();
		$authenticationManager->logout(['httpEvent' => $event]);
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			if (isset($cookie['RBSCHANGE_AUTOLOGIN']))
			{
				$this->deleteCurrentToken($transactionManager, $dbProvider, $currentUser->getId(), $cookie['RBSCHANGE_AUTOLOGIN']);
				setcookie('RBSCHANGE_AUTOLOGIN', '', (new \DateTime())->getTimestamp(), '/');
			}

			$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
			unset($session[$website->getId()]);
			$data = array();
		}
		else
		{
			$data = array('error' => 'Invalid website');
		}

		$result = new \Change\Http\Web\Result\AjaxResult($data);
		$event->setResult($result);
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $userId
	 * @param string $token
	 */
	protected function deleteCurrentToken($transactionManager, $dbProvider, $userId, $token)
	{
		try
		{
			$transactionManager->begin();

			// Delete all token for this user_id
			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->delete($fb->table('rbs_user_auto_login'));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('token'), $fb->parameter('token')),
				$fb->eq($fb->column('user_id'), $fb->parameter('userId'))
			));
			$dq = $qb->deleteQuery();

			$dq->bindParameter('token', $token);
			$dq->bindParameter('userId', $userId);
			$dq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$transactionManager->rollBack($e);
		}

	}
}