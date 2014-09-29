<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Ajax;

/**
 * @name \Rbs\User\Http\Ajax\Authentication
 */
class Authentication
{
	const DEFAULT_NAMESPACE = 'Authentication';

	/**
	 * Default actionPath: Rbs/User/Login
	 * Event params:
	 *  - website, websiteUrlManager, section, page
	 *  - data: login, password, realm, rememberMe
	 * @param \Change\Http\Event $event
	 */
	public function login(\Change\Http\Event $event)
	{
		$data = $this->validateOptions($event->getParam('data'));
		$website = $event->getParam('website');

		$realm = $data['realm'];
		$login = $data['login'];
		$password = $data['password'];
		$rememberMe = $data['rememberMe'];
		$device = $data['device'];
		if ($realm && $login && $password)
		{
			$authenticationManager = $event->getApplicationServices()->getAuthenticationManager();
			$user = $authenticationManager->login($login, $password, $realm, ['httpEvent' => $event]);
			if ($user instanceof \Change\User\UserInterface)
			{
				$authenticationManager->setCurrentUser($user);
				$authenticationManager->setConfirmed(true);
				$accessorId = $user->getId();
				$this->save($website, $accessorId, true);
				$resultData = ['user' => ['accessorId' => $accessorId, 'name' => $user->getName()]];

				if ($rememberMe)
				{
					// Save token
					$timestamp = new \DateTime();
					$token = md5($login . $realm . $timestamp->getTimestamp());
					$endDate = new \DateTime();
					$endDate->add(new \DateInterval('P6M'));
					$this->saveAutoLoginToken($event->getApplicationServices()->getTransactionManager(),
						$event->getApplicationServices()->getDbProvider(), $accessorId, $token, $device, $endDate);
					$resultData['user']['RBSCHANGE_AUTOLOGIN'] = $token;
					setcookie('RBSCHANGE_AUTOLOGIN', $token, $endDate->getTimestamp(), '/');
				}
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/Login', $resultData);
			}
			else
			{
				$i18nManager = $event->getApplicationServices()->getI18nManager();
				$result = new \Change\Http\Ajax\V1\ErrorResult('error_login_password_not_match',
					$i18nManager->trans('m.rbs.user.front.error_login_password_not_match', array('ucf')),
					\Zend\Http\Response::STATUS_CODE_409);
				unset($data['password']);
				$result->setData($data);
			}
		}
		else
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$result = new \Change\Http\Ajax\V1\ErrorResult('error_login_password_not_match',
				$i18nManager->trans('m.rbs.user.front.error_login_password_not_match', array('ucf')),
				\Zend\Http\Response::STATUS_CODE_409);
			unset($data['password']);
			$result->setData($data);
		}
		$event->setResult($result);
	}


	public function validateOptions($rawOptions)
	{
		$options = ['realm' => null, 'login' => null, 'password' => null, 'rememberMe' => false, 'device' => null];
		if (is_array($rawOptions))
		{
			foreach ($rawOptions as $k => $v)
			{
				switch ($k)
				{
					case 'realm';
					case 'login';
					case 'password';
					case 'device';
						if (is_string($v) && !\Change\Stdlib\String::isEmpty($v))
						{
							$options[$k] = trim($v);
						}
						break;
					case 'rememberMe';
						if (is_string($v))
						{
							$v = ($v === 'true' || $v === '1');
						}
						$options[$k] = ($v == true);
						break;
				}
			}
		}
		return $options;
	}

	/**
	 * Default actionPath: Rbs/User/Logout
	 * Event params:
	 *  - website
	 * @param \Change\Http\Event $event
	 */
	public function logout(\Change\Http\Event $event)
	{
		$cookie = $event->getRequest()->getCookie();
		$authenticationManager = $event->getAuthenticationManager();
		$currentUser = $authenticationManager->getCurrentUser();
		$authenticationManager->logout(['httpEvent' => $event]);
		$website = $event->getParam('website');

		if (isset($cookie['RBSCHANGE_AUTOLOGIN']))
		{
			$transactionManager = $event->getApplicationServices()->getTransactionManager();
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$this->deleteCurrentToken($transactionManager, $dbProvider, $currentUser->getId(), $cookie['RBSCHANGE_AUTOLOGIN']);
			setcookie('RBSCHANGE_AUTOLOGIN', '', (new \DateTime())->getTimestamp(), '/');
		}

		$this->save($website, null, false);
		$data = ['user' => ['logoutAccessorId' => $currentUser->getId()]];

		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/Logout', $data);
		$event->setResult($result);
	}

	/**
	 * Default actionPath: Rbs/User/Info
	 * Event params:
	 * @param \Change\Http\Event $event
	 */
	public function info(\Change\Http\Event $event)
	{
		$authenticationManager = $event->getAuthenticationManager();
		$currentUser = $authenticationManager->getCurrentUser();
		$resultData = ['user' => ['accessorId' => $currentUser->getId(), 'name' => $currentUser->getName()]];
		$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/Info', $resultData);
		$event->setResult($result);
	}


	/**
	 * Default actionPath: Rbs/User/CheckEmailAvailability
	 * Event params:
	 *  - data: email
	 * @param \Change\Http\Event $event
	 */
	public function checkEmailAvailability(\Change\Http\Event $event)
	{
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['email'])) {

			$email = $data['email'];
			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
			$dqb->andPredicates($dqb->eq('email', $email));
			$count = $dqb->getCountDocuments();
			if ($count > 0)
			{
				$errorMessage = $event->getApplicationServices()->getI18nManager()->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]);
				$result = new \Change\Http\Ajax\V1\ErrorResult('999999', $errorMessage, \Zend\Http\Response::STATUS_CODE_409);
			}
			else
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/Info', ['user' => ['availableEmail' => $email]]);
			}
			$event->setResult($result);
		}
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


	/**
	 * @param \Change\Presentation\Interfaces\Website|integer|null $website
	 * @param integer $accessorId
	 * @param boolean $confirmed
	 */
	protected function save($website, $accessorId, $confirmed)
	{
		$websiteId =  ($website instanceof \Change\Presentation\Interfaces\Website) ? $website->getId() : (is_int($website)? $website : 0);
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if ($accessorId === null || $accessorId === false)
		{
			unset($session[$websiteId]);
			if ($websiteId)
			{
				unset($session[0]);
			}
		}
		else
		{
			$sessionData = ['id' => $accessorId, 'confirmed' => $confirmed];
			$session[$websiteId] = $sessionData;
			if ($websiteId)
			{
				$session[0] = $sessionData;
			}
		}
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website|integer|null $website
	 * @return array|null
	 */
	protected function load($website = null)
	{
		$websiteId =  ($website instanceof \Change\Presentation\Interfaces\Website) ? $website->getId() : (is_int($website)? $website : 0);
		$session = new \Zend\Session\Container(static::DEFAULT_NAMESPACE);
		if (isset($session[$websiteId]))
		{
			return $session[$websiteId];
		}
		elseif (isset($session[0]))
		{
			return $session[0];
		}
		return null;
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
			$iq->bindParameter('device', $device ? $device : 'Unknown');
			$iq->bindParameter('validityDate', $validityDate);
			$iq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	public function authenticate(\Change\Http\Event $event)
	{
		$website = $event->getParam('website');
		$sessionInfo = $this->load($website);
		if (is_int($sessionInfo['id']))
		{
			$authenticationManager = $event->getAuthenticationManager();
			$user = $authenticationManager->getById($sessionInfo['id']);
			if ($user instanceof \Change\User\UserInterface)
			{
				$authenticationManager->setCurrentUser($user);
				$authenticationManager->setConfirmed($sessionInfo['confirmed']);
			}
			else
			{
				throw new \RuntimeException('Invalid AccessorId: ' . $sessionInfo['id'], 999999);
			}
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function authenticateFromCookie(\Change\Http\Event $event)
	{
		$authenticationManager = $event->getAuthenticationManager();
		$user = $authenticationManager->getCurrentUser();
		if (!$user->authenticated())
		{
			$token = null;
			$website = $event->getParam('website');
			$cookie = $event->getRequest()->getCookie();
			if (isset($cookie['RBSCHANGE_AUTOLOGIN']))
			{
				$token = $cookie['RBSCHANGE_AUTOLOGIN'];
			}
			else
			{
				$options = $event->getParam('options');
				if (is_array($options) && isset($options['RBSCHANGE_AUTOLOGIN']))
				{
					$token = $options['RBSCHANGE_AUTOLOGIN'];
				}
			}

			if (is_string($token))
			{
				$user = $authenticationManager->login('RBSCHANGE_AUTOLOGIN', $token, 'auto_login', ['httpEvent' => $event]);
				if ($user instanceof \Change\User\UserInterface)
				{
					$authenticationManager->setCurrentUser($user);
					$authenticationManager->setConfirmed(false);
					$accessorId = $user->getId();
					$this->save($website, $accessorId, false);
				}
				else
				{
					setcookie('RBSCHANGE_AUTOLOGIN', '', (new \DateTime())->getTimestamp() - 3600, '/');
				}
			}
		}
	}
} 