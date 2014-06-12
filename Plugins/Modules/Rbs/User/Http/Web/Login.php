<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Change\Http\Web\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Web\Login
 */
class Login extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * @param Event $event
	 * @return mixed
	 */
	public function execute(Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$this->login($event);
		}
	}

	/**
	 * @param Event $event
	 */
	protected function login(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$data = $event->getRequest()->getPost()->toArray();
			$realm = $data['realm'];
			$login = $data['login'];
			$rememberMe = isset($data['rememberMe']) ? true : false;
			$device = $data['device'];
			$password = $data['password'];
			unset($data['password']);

			$i18nManager = $event->getApplicationServices()->getI18nManager();

			if ($realm && $login && $password)
			{
				$am = $event->getAuthenticationManager();
				$user = $am->login($login, $password, $realm, ['httpEvent' => $event]);
				if ($user instanceof \Change\User\UserInterface)
				{
					$am->setCurrentUser($user);
					$accessorId = $user->getId();
					$this->save($website, $accessorId);
					$data = array('accessorId' => $accessorId, 'name' => $user->getName());

					if ($rememberMe)
					{
						// Save token
						$timestamp = new \DateTime();
						$token = md5($login . $realm . $timestamp->getTimestamp());
						$endDate = new \DateTime();
						$endDate->add(new \DateInterval('P6M'));
						$this->saveAutoLoginToken($event->getApplicationServices()->getTransactionManager(),
							$event->getApplicationServices()->getDbProvider(), $accessorId, $token, $device, $endDate);

						setcookie('RBSCHANGE_AUTOLOGIN', $token, $endDate->getTimestamp(), '/');
					}
				}
				else
				{
					$data['errors'] = [$i18nManager->trans('m.rbs.user.front.error_login_password_not_match', array('ucf'))];
				}
			}
			else
			{
				$data['errors'] = array();
				if (!$realm)
				{
					$data['errors'][] = 'Realm is empty';
				}
				if (!$login)
				{
					$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_login', ['ucf']);
				}
				if (!$password)
				{
					$data['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']);
				}
			}
		}
		else
		{
			$data = array('errors' => ['Invalid website']);
		}

		$result = new \Change\Http\Web\Result\AjaxResult($data);
		if (isset($data['errors']) && count($data['errors']) > 0)
		{
			$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
		}
		$event->setResult($result);
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param integer $accessorId
	 */
	protected function save(\Change\Presentation\Interfaces\Website $website, $accessorId)
	{
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if ($accessorId === null || $accessorId === false)
		{
			unset($session[$website->getId()]);
		}
		else
		{
			$session[$website->getId()] = $accessorId;
		}
	}

	/**
	 * @param \Change\Transaction\TransactionManager $transactionManager
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $userId
	 * @param string $token
	 * @param string $device
	 * @param \DateTime $validityDate
	 * @throws \Exception
	 */
	protected function saveAutoLoginToken($transactionManager, $dbProvider, $userId, $token, $device, $validityDate)
	{
		try
		{
			$transactionManager->begin();

			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->insert($fb->table('rbs_user_auto_login'));
			$qb->addColumns($fb->column('user_id'), $fb->column('token'), $fb->column('device'), $fb->column('validity_date'));
			$qb->addValues($fb->parameter('user_id'), $fb->parameter('token'), $fb->parameter('device'), $fb->dateTimeParameter('validityDate'));
			$iq = $qb->insertQuery();

			$iq->bindParameter('user_id', $userId);
			$iq->bindParameter('token', $token);
			$iq->bindParameter('device', $device);
			$iq->bindParameter('validityDate', $validityDate);
			$iq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return integer|null
	 */
	protected function load(\Change\Presentation\Interfaces\Website $website)
	{
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if (isset($session[$website->getId()]))
		{
			return $session[$website->getId()];
		}
		return null;
	}

	/**
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function authenticate(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			$accessorId = $this->load($website);
			if (is_int($accessorId))
			{
				$user = $event->getAuthenticationManager()->getById($accessorId);
				if ($user instanceof \Change\User\UserInterface)
				{
					$event->getAuthenticationManager()->setCurrentUser($user);
				}
				else
				{
					throw new \RuntimeException('Invalid AccessorId: ' . $accessorId, 999999);
				}
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function loginFromCookie(Event $event)
	{
		$website = $event->getParam('website');
		if ($website instanceof \Change\Presentation\Interfaces\Website)
		{
			if ($event->getAuthenticationManager()->getCurrentUser()->getId() == null)
			{
				$cookie = $event->getRequest()->getCookie();
				if (isset($cookie['RBSCHANGE_AUTOLOGIN']))
				{
					$qb = $event->getApplicationServices()->getDbProvider()->getNewQueryBuilder();
					$fb = $qb->getFragmentBuilder();
					$qb->select($fb->column('user_id'));
					$qb->from($fb->table('rbs_user_auto_login'));
					$qb->where($fb->logicAnd(
						$fb->eq($fb->column('token'), $fb->parameter('token')),
						$fb->gt($fb->column('validity_date'), $fb->dateTimeParameter('validityDate'))
					));
					$sq = $qb->query();

					$sq->bindParameter('token', $cookie['RBSCHANGE_AUTOLOGIN']);
					$now = new \DateTime();
					$sq->bindParameter('validityDate', $now);

					$result = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('user_id'));

					if ($result)
					{
						$this->save($website, $result);
						$this->authenticate($event);
					}
				}
			}
		}
	}
}