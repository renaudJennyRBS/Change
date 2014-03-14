<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\ResetPasswordConfirmation
*/
class ResetPasswordConfirmation extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return \Change\Http\Web\Result\AjaxResult|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$dbProvider = $event->getApplicationServices()->getDbProvider();

		if ($event->getRequest()->getMethod() === 'GET')
		{
			$token = $event->getRequest()->getQuery('token');

			if (!$this->checkTokenValidity($dbProvider, $token))
			{
				$token = null;
			}

			$urlManager = $event->getUrlManager();
			$urlManager->setAbsoluteUrl(true);
			$redirectURL = $urlManager->getByFunction('Rbs_User_ResetPassword', null, ['token' => $token]);
			$event->setParam('redirectLocation', $redirectURL);
			$event->setParam('errorLocation', $redirectURL);

			$event->setResult($this->getNewAjaxResult());
		}
		elseif($event->getRequest()->getMethod() === 'POST')
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$token = $event->getRequest()->getPost('token');
			$context = null;
			$errors = array();
			$result = new \Change\Http\Web\Result\AjaxResult();

			if (!$this->checkTokenValidity($dbProvider, $token))
			{
				$token = null;
			}
			else
			{
				$password = $event->getRequest()->getPost('password');
				$confirmPassword = $event->getRequest()->getPost('confirmPassword');

				if (!$password)
				{
					$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']);
				}
				if ($password !== $confirmPassword)
				{
					$errors[] = $i18nManager->trans('m.rbs.user.front.error_password_not_match_confirm_password', ['ucf']);
				}

				if (count($errors) > 0)
				{
					$result->setEntry('errors', $errors);
					$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
				}
				else
				{
					$tm = $event->getApplicationServices()->getTransactionManager();
					$documentManager = $event->getApplicationServices()->getDocumentManager();

					$this->updatePassword($dbProvider, $tm, $documentManager, $token, $password);

					$context = 'success';
				}
			}

			$urlManager = $event->getUrlManager();
			$urlManager->setAbsoluteUrl(true);
			$redirectURL = $urlManager->getByFunction('Rbs_User_ResetPassword', null, ['token' => $token, 'context' => $context]);
			$event->setParam('redirectLocation', $redirectURL);
			$event->setParam('errorLocation', $redirectURL);

			$event->setResult($result);
		}
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param string $token
	 * @return boolean
	 */
	protected function checkTokenValidity ($dbProvider, $token)
	{
		// Check if email match the request, and check if the date is still valid.
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('request_id'));
		$qb->from($fb->table('rbs_user_reset_password'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('token'), $fb->parameter('token')),
			$fb->gt($fb->column('request_date'), $fb->dateTimeParameter('validityDate'))
		));
		$sq = $qb->query();

		$sq->bindParameter('token', $token);
		// Check the validity of the request by comparing date (delta of 24h after the request).
		$now = new \DateTime();
		$sq->bindParameter('validityDate', $now->sub(new \DateInterval('PT24H')));

		$queryResult = $sq->getFirstResult();

		return $queryResult ? true : false;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param \Change\Transaction\TransactionManager $tm
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param string $token
	 * @param string $password
	 * @throws \Exception
	 */
	protected function updatePassword($dbProvider, $tm, $documentManager, $token, $password)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('user_id'));
		$qb->from($fb->table('rbs_user_reset_password'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('token'), $fb->parameter('token'))
		));
		$sq = $qb->query();

		$sq->bindParameter('token', $token);

		$queryResult = $sq->getFirstResult();

		if ($queryResult)
		{
			$userId = $queryResult['user_id'];

			try
			{
				$tm->begin();

				// Update user password
				/* @var $user \Rbs\User\Documents\User */
				$user = $documentManager->getDocumentInstance($userId, 'Rbs_User_User');
				$user->setPassword($password);
				$user->save();

				// Delete all token for this user_id
				$qb = $dbProvider->getNewStatementBuilder();
				$fb = $qb->getFragmentBuilder();

				$qb->delete($fb->table('rbs_user_reset_password'));
				$qb->where($fb->logicAnd(
					$fb->eq($fb->column('user_id'), $fb->parameter('user_id'))
				));
				$dq = $qb->deleteQuery();

				$dq->bindParameter('user_id', $userId);
				$dq->execute();

				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
		}
	}
}