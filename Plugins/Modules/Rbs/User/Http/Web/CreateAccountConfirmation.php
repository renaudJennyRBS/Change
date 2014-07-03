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
* @name \Rbs\User\Http\Web\CreateAccountConfirmation
*/
class CreateAccountConfirmation extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		if ($event->getRequest()->getMethod() === 'GET')
		{
			$data = $event->getRequest()->getQuery()->toArray();
			$urlManager = $event->getUrlManager();
			$urlManager->setAbsoluteUrl(true);

			$redirectURL = $urlManager->getByFunction('Rbs_User_CreateAccountSuccess');
			if (!$redirectURL)
			{
				$redirectURL = $urlManager->getByFunction('Rbs_User_CreateAccount', null, ['context' => 'create']);
			}
			$event->setParam('redirectLocation', $redirectURL);
			$event->setParam('errorLocation', $redirectURL);

			$email = $data['email'];
			// Get request parameters or errors.
			$requestParameters = $this->getRequestParameters($event);
			$params = isset($requestParameters['params']) ? $requestParameters['params'] : null;
			if ($params && count($requestParameters['errors']) === 0)
			{
				$this->createUser($event, $email, $params);
				$result = new \Change\Http\Web\Result\AjaxResult($data);
				$event->setResult($result);
			}
			else
			{
				$result = new \Change\Http\Web\Result\AjaxResult(['errors' => $requestParameters['errors']]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
				$event->setResult($result);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \Exception
	 * @return array
	 */
	protected function getRequestParameters(\Change\Http\Web\Event $event)
	{
		$result = [];
		$result['errors'] = [];
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$data = $event->getRequest()->getQuery()->toArray();

		$requestId = $data['requestId'];
		$email = $data['email'];
		if (!$requestId)
		{
			$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_request_id', ['ucf']);
		}
		if (!$email)
		{
			$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']);
		}
		if ($requestId && $email)
		{
			// Check if email match the request, and check if the date is still valid.
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('config_parameters'));
			$qb->from($fb->table('rbs_user_account_request'));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('request_id'), $fb->integerParameter('requestId')),
				$fb->eq($fb->column('email'), $fb->parameter('email')),
				$fb->gt($fb->column('request_date'), $fb->dateTimeParameter('validityDate'))
			));
			$sq = $qb->query();

			$sq->bindParameter('requestId', $requestId);
			$sq->bindParameter('email', $email);
			// Check the validity of the request by comparing date (delta of 24h after the request).
			$now = new \DateTime();
			$sq->bindParameter('validityDate', $now->sub(new \DateInterval('PT24H')));
			$requestParameters = $sq->getFirstResult($sq->getRowsConverter()->addTxtCol('config_parameters'));

			if (!$requestParameters)
			{
				$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_request_expired', ['ucf']);
			}
			else
			{
				$params = json_decode($requestParameters, true);

				// Check if the user already exists.
				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
				$dqb->andPredicates($dqb->eq('email', $email));
				$count = $dqb->getCountDocuments();
				if ($count !== 0)
				{
					$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]);
				}
				else
				{
					if (!isset($params['passwordHash']) || !$params['passwordHash'])
					{
						$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_password_hash', ['ucf']);
					}
					else
					{
						$result['params'] = $params;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @param string $email
	 * @param array $params
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @return \Rbs\User\Documents\User
	 */
	protected function getNewUserFromParams($email, $params, $documentManager)
	{
		$user = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_User');
		/* @var $user \Rbs\User\Documents\User */
		$user->setEmail($email);
		$user->setHashMethod($params['hashMethod']);
		$user->setPasswordHash($params['passwordHash']);
		return $user;
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @param $email
	 * @param $params
	 * @throws \Exception
	 * @return \Rbs\User\Documents\User
	 */
	public function createUser(\Change\Http\Web\Event $event, $email, $params)
	{
		$user = $this->getNewUserFromParams($email, $params, $event->getApplicationServices()->getDocumentManager());
		//TODO: RBSChange/evolutions#70 : allow groups configuration in backoffice
		// At the moment, just give web access to the user by put him in "web" realm group.
		$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_Group');
		$dqb->andPredicates($dqb->eq('realm', 'web'));
		$group = $dqb->getFirstDocument();
		if (!$group)
		{
			throw new \Exception('Group with realm "web" doesn\'t exist', 999999);
		}
		$user->getGroups()->add($group);

		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$user->save();
			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		return $user;
	}
}