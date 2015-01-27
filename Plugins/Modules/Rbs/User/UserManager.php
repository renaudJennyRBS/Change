<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User;

/**
* @name \Rbs\User\UserManager
*/
class UserManager implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	const EVENT_MANAGER_IDENTIFIER = 'Rbs_User_UserManager';


	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('createAccountRequest', [$this, 'onDefaultCheckCreateAccountRequest'], 10);
		$eventManager->attach('createAccountRequest', [$this, 'onDefaultCreateAccountRequest'], 5);

		$eventManager->attach('confirmAccountRequest', [$this, 'onDefaultCheckConfirmAccountRequest'], 10);
		$eventManager->attach('confirmAccountRequest', [$this, 'onDefaultConfirmAccountRequest'], 5);

		$eventManager->attach('createResetPasswordRequest', [$this, 'onDefaultCheckCreateResetPasswordRequest'], 10);
		$eventManager->attach('createResetPasswordRequest', [$this, 'onDefaultCreateResetPasswordRequest'], 5);

		$eventManager->attach('confirmResetPasswordRequest', [$this, 'onDefaultCheckConfirmResetPasswordRequest'], 10);
		$eventManager->attach('confirmResetPasswordRequest', [$this, 'onDefaultConfirmResetPasswordRequest'], 5);

		$eventManager->attach('changePassword', [$this, 'onDefaultCheckChangePassword'], 10);
		$eventManager->attach('changePassword', [$this, 'onDefaultChangePassword'], 5);

		$eventManager->attach('getUserData', [$this, 'onDefaultGetUserData'], 5);
		$eventManager->attach('setUserData', [$this, 'onDefaultSetUserData'], 5);
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return static::EVENT_MANAGER_IDENTIFIER;
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		return $this->getApplication()->getConfiguredListenerClassNames('Rbs/User/Events/UserManager');
	}

	/**
	 * @var string[]
	 */
	protected $errors = [];

	/**
	 * @return string[]
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * @return string|boolean
	 */
	public function getLastError()
	{
		if ($this->hasErrors())
		{
			return $this->errors[count($this->errors) - 1];
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function hasErrors()
	{
		return count($this->errors) != 0;
	}

	/**
	 * @return $this
	 */
	public function resetErrors()
	{
		$this->errors = [];
		return $this;
	}

	/**
	 * @param string $error
	 * @return $this
	 */
	public function addError($error)
	{
		if (is_string($error) && !\Change\Stdlib\String::isEmpty($error))
		{
			$this->errors[] = $error;
		}
		return $this;
	}

	/**
	 * @param string $email
	 * @param array $requestParameters
	 * @return integer|boolean
	 */
	public function createAccountRequest($email, array $requestParameters)
	{
		$this->resetErrors();
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['email' => $email, 'requestParameters' => $requestParameters]);
		$eventManager->trigger('createAccountRequest', $this, $args);
		if (isset($args['requestId']))
		{
			return $args['requestId'];
		}
		return false;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCheckCreateAccountRequest($event)
	{
		$email = $event->getParam('email');
		$requestParameters = $event->getParam('requestParameters');

		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();
		if (!is_string($email) || \Change\Stdlib\String::isEmpty($email))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']));
			return;
		}

		$validator = new \Zend\Validator\EmailAddress();
		if (!$validator->isValid($email))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_email_invalid', ['ucf'], ['EMAIL' => $email]));
			return;
		}

		if (!isset($requestParameters['password']) || \Change\Stdlib\String::isEmpty($requestParameters['password']))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']));
			return;
		}

		$password = trim(strval($requestParameters['password']));
		unset($requestParameters['password']);
		if (strlen($password) > 50)
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_password_exceeds_max_characters', ['ucf']));
			return;
		}

		if ($this->userEmailExists($applicationServices->getDocumentManager(), $email))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]));
			return;
		}

		if ($this->accountRequestExists($applicationServices->getDbProvider(), $email))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_request_already_done', ['ucf'], ['EMAIL' => $email]));
			return;
		}

		$encodedPassword = $this->encodeUserPassword($applicationServices->getDocumentManager(), $password);
		$event->setParam('requestParameters', array_merge($requestParameters, $encodedPassword));
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCreateAccountRequest($event)
	{
		if ($event->getParam('requestId') || $this->hasErrors())
		{
			return;
		}

		$email = $event->getParam('email');
		$parameters = $event->getParam('requestParameters');

		$applicationServices = $event->getApplicationServices();
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();
			$requestId = $this->insertAccountRequest($applicationServices->getDbProvider(), $email, $parameters);
			$event->setParam('requestId', $requestId);
			if (isset($parameters['websiteId']))
			{
				$website = $applicationServices->getDocumentManager()->getDocumentInstance($parameters['websiteId']);
				if ($website instanceof \Rbs\Website\Documents\Website)
				{
					/* @var \Rbs\Generic\GenericServices $genericServices */
					$genericServices = $event->getServices('genericServices');
					$this->sendMailAccountRequest($genericServices->getMailManager(), $email, $requestId, $website, $parameters);
				}
			}

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$event->getApplicationServices()->getLogging()->exception($e);
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param integer $requestId
	 * @param string $email
	 * @return boolean|\Rbs\User\Documents\User
	 */
	public function confirmAccountRequest($requestId, $email)
	{
		$this->resetErrors();
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['email' => $email, 'requestId' => $requestId]);
		$eventManager->trigger('confirmAccountRequest', $this, $args);
		if (isset($args['user']) && $args['user'] instanceof \Rbs\User\Documents\User)
		{
			return $args['user'];
		}
		return false;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCheckConfirmAccountRequest($event)
	{
		$email = $event->getParam('email');
		$requestId = $event->getParam('requestId');
		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();
		$parameters = $this->loadAccountRequestParameters($applicationServices->getDbProvider(), $requestId, $email);
		if (!$parameters)
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_request_expired', ['ucf']));
			return;
		}

		if (!isset($parameters['passwordHash']) || !$parameters['passwordHash'])
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_empty_password_hash', ['ucf']));
			return;
		}
		$event->setParam('requestParameters', $parameters);

		/* @var $user \Rbs\User\Documents\User */
		// Check if the user already exists.
		$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_User');
		$dqb->andPredicates($dqb->eq('email', $email));
		$user = $dqb->getFirstDocument();
		if ($user instanceof \Rbs\User\Documents\User)
		{
			if ($user->getPasswordHash() !== $parameters['passwordHash'])
			{
				$this->addError($i18nManager->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]));
				return;
			}
			else
			{
				$event->setParam('user', $user);
				$event->setParam('duplicateConfirmation', true);
				return;
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultConfirmAccountRequest($event)
	{
		if ($event->getParam('user') || $this->hasErrors() || !$event->getParam('requestParameters'))
		{
			return;
		}

		$email = $event->getParam('email');
		$requestId = $event->getParam('requestId');
		$requestParameters = $event->getParam('requestParameters');
		$applicationServices = $event->getApplicationServices();

		$documentManager = $applicationServices->getDocumentManager();
		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			/* @var $user \Rbs\User\Documents\User */
			$user = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_User');

			$user->setEmail($email);
			$user->setHashMethod($requestParameters['hashMethod']);
			$user->setPasswordHash($requestParameters['passwordHash']);

			$realm = isset($requestParameters['realm']) ? $requestParameters['realm'] : 'web';
			$dqb = $documentManager->getNewQuery('Rbs_User_Group');
			$dqb->andPredicates($dqb->eq('realm', $realm));
			$group = $dqb->getFirstDocument();
			if ($group)
			{
				$user->getGroups()->add($group);
			}

			$user->save();
			$event->setParam('user', $user);

			// Delete all request for this email
			$qb = $applicationServices->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->delete($fb->table('rbs_user_account_request'));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('email'), $fb->parameter('email'))
			));

			$dq = $qb->deleteQuery();
			$dq->bindParameter('email', $email);
			$dq->execute();

			if (isset($requestParameters['websiteId']))
			{
				$website = $applicationServices->getDocumentManager()->getDocumentInstance($requestParameters['websiteId']);
				if ($website instanceof \Rbs\Website\Documents\Website)
				{
					/* @var \Rbs\Generic\GenericServices $genericServices */
					$genericServices = $event->getServices('genericServices');
					$this->sendMailAccountConfirmed($genericServices->getMailManager(), $email, $requestId, $website, $requestParameters);
				}
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param string $email
	 * @param array $requestParameters
	 * @return integer|boolean
	 */
	public function createResetPasswordRequest($email, array $requestParameters)
	{
		$this->resetErrors();
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['email' => $email, 'requestParameters' => $requestParameters]);
		$eventManager->trigger('createResetPasswordRequest', $this, $args);
		if (isset($args['requestId']))
		{
			return $args['requestId'];
		}
		return false;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCheckCreateResetPasswordRequest($event)
	{
		$email = $event->getParam('email');
		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();
		if (!is_string($email) || \Change\Stdlib\String::isEmpty($email))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']));
			return;
		}

		$validator = new \Zend\Validator\EmailAddress();
		if (!$validator->isValid($email))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_email_invalid', ['ucf'], ['EMAIL' => $email]));
			return;
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCreateResetPasswordRequest($event)
	{
		if ($event->getParam('requestId') || $this->hasErrors())
		{
			return;
		}

		$email = $event->getParam('email');
		$parameters = $event->getParam('requestParameters');

		$applicationServices = $event->getApplicationServices();
		$transactionManager = $applicationServices->getTransactionManager();
		$event->setParam('requestId', true);
		try
		{
			$transactionManager->begin();
			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_User');
			$dqb->andPredicates($dqb->eq('email', $email));
			$user = $dqb->getFirstDocument();
			if ($user)
			{
				$dbProvider = $applicationServices->getDbProvider();
				$userId = $user->getId();
				$validRequestDate = new \DateTime();
				$validRequestDate->add(new \DateInterval('PT24H'));
				$token = md5($email . $validRequestDate->getTimestamp());

				$requestId = $this->insertResetPasswordRequest($dbProvider, $userId, $token, $validRequestDate);
				$event->setParam('requestId', $requestId);

				if (isset($parameters['websiteId']))
				{
					$website = $applicationServices->getDocumentManager()->getDocumentInstance($parameters['websiteId']);
					if ($website instanceof \Rbs\Website\Documents\Website)
					{
						/* @var \Rbs\Generic\GenericServices $genericServices */
						$genericServices = $event->getServices('genericServices');
						$this->sendMailResetPasswordRequest($genericServices->getMailManager(), $email, $token, $website, $parameters);
					}
				}
			}
			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$event->getApplicationServices()->getLogging()->exception($e);
			throw $transactionManager->rollBack($e);
		}
	}


	/**
	 * @param string $token
	 * @param string $password
	 * @return boolean|\Rbs\User\Documents\User
	 */
	public function confirmResetPasswordRequest($token, $password)
	{
		$this->resetErrors();
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['token' => $token, 'password' => $password]);
		$eventManager->trigger('confirmResetPasswordRequest', $this, $args);
		if (isset($args['user']) && $args['user'] instanceof \Rbs\User\Documents\User)
		{
			return $args['user'];
		}
		return false;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCheckConfirmResetPasswordRequest($event)
	{
		$token = $event->getParam('token');
		$password = trim(strval($event->getParam('password')));

		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();

		if (\Change\Stdlib\String::isEmpty($password))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']));
			return;
		}

		if (strlen($password) > 50)
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_password_exceeds_max_characters', ['ucf']));
			return;
		}

		$qb = $applicationServices->getDbProvider()->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('user_id'));
		$qb->from($fb->table('rbs_user_reset_password'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('token'), $fb->parameter('token')),
			$fb->gt($fb->column('request_date'), $fb->dateTimeParameter('validityDate'))
		));
		$sq = $qb->query();

		$sq->bindParameter('token', $token);

		// Check the validity of the request by comparing date (delta of 24h after the request).
		$sq->bindParameter('validityDate', new \DateTime());

		$userId = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('user_id')->singleColumn('user_id'));
		if ($userId)
		{
			$event->setParam('userId', $userId);
			$user = $applicationServices->getDocumentManager()->getDocumentInstance($userId);
			if ($user instanceof \Rbs\User\Documents\User)
			{
				$event->setParam('user', $user);
				return;
			}
		}

		$this->addError($i18nManager->trans('m.rbs.user.front.invalid_token', ['ucf']));
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultConfirmResetPasswordRequest($event)
	{
		if (!$event->getParam('user') || !$event->getParam('password') || $this->hasErrors())
		{
			return;
		}

		$password = $event->getParam('password');
		$applicationServices = $event->getApplicationServices();

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			/* @var $user \Rbs\User\Documents\User */
			$user = $event->getParam('user');

			$user->setPassword($password);
			$user->save();

			// Delete all token for this user_id
			$qb = $applicationServices->getDbProvider()->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->delete($fb->table('rbs_user_reset_password'));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('user_id'), $fb->parameter('user_id'))
			));
			$dq = $qb->deleteQuery();
			$dq->bindParameter('user_id', $user->getId());
			$dq->execute();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param string $currentPassword
	 * @param string $password
	 * @return boolean|\Rbs\User\Documents\User
	 */
	public function changePassword($currentPassword, $password)
	{
		$this->resetErrors();
		$eventManager = $this->getEventManager();
		$args = $eventManager->prepareArgs(['currentPassword' => $currentPassword, 'password' => $password]);
		$eventManager->trigger('changePassword', $this, $args);
		if (isset($args['user']) && $args['user'] instanceof \Rbs\User\Documents\User)
		{
			return $args['user'];
		}
		return false;
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultCheckChangePassword($event)
	{
		$currentPassword = trim(strval($event->getParam('currentPassword')));
		$password = trim(strval($event->getParam('password')));

		$applicationServices = $event->getApplicationServices();
		$i18nManager = $applicationServices->getI18nManager();
		if (\Change\Stdlib\String::isEmpty($currentPassword) || \Change\Stdlib\String::isEmpty($password))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']));
			return;
		}
		if (strlen($password) > 50)
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.error_password_exceeds_max_characters', ['ucf']));
			return;
		}

		$authenticationManager = $applicationServices->getAuthenticationManager();
		$user = $authenticationManager->getCurrentUser()->authenticated() ?
			$applicationServices->getDocumentManager()->getDocumentInstance($authenticationManager->getCurrentUser()->getId()) : null;
		if (!($user instanceof \Rbs\User\Documents\User))
		{
			$this->addError($i18nManager->trans('m.rbs.user.front.user_not_found', ['ucf']));
			return;
		}

		if (!$user->checkPassword($currentPassword)) {
			$this->addError($i18nManager->trans('m.rbs.user.front.current_password_not_match', ['ucf']));
			return;
		}

		$event->setParam('user', $user);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function onDefaultChangePassword($event)
	{
		if (!$event->getParam('user') || !$event->getParam('password') || $this->hasErrors())
		{
			return;
		}

		$password = $event->getParam('password');
		$applicationServices = $event->getApplicationServices();

		$transactionManager = $applicationServices->getTransactionManager();
		try
		{
			$transactionManager->begin();

			/* @var $user \Rbs\User\Documents\User */
			$user = $event->getParam('user');

			$user->setPassword($password);
			$user->save();

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			throw $transactionManager->rollBack($e);
		}
	}

	/**
	 * @param \Rbs\Mail\MailManager $mailManager
	 * @param string $email
	 * @param integer $requestId
	 * @param \Rbs\Website\Documents\Website $website
	 * @param array $params
	 */
	public function sendMailAccountRequest(\Rbs\Mail\MailManager $mailManager, $email, $requestId, \Rbs\Website\Documents\Website $website, $params = [])
	{
		$LCID = isset($params['LCID']) ? $params['LCID'] : $website->getCurrentLCID();
		$urlManager = $website->getUrlManager($LCID);
		$old = $urlManager->absoluteUrl(true);

		if (!isset($params['link']))
		{
			if (!isset($params['confirmationPage']) || !$params['confirmationPage'])
			{
				$location = $urlManager->getByFunction('Rbs_User_CreateAccount', ['requestId' => $requestId, 'email' => $email]);
				$params['link'] = $location ? $location->normalize()->toString(): null;
			}

			if (!isset($params['link']))
			{
				$link = $urlManager->getAjaxURL('Rbs_User', 'CreateAccountConfirmation', ['requestId' => $requestId, 'email' => $email]);
				$params['link'] = $link;
			}
		}

		$params += ['website' => $website->getTitle(), 'email' => $email, 'requestId' => $requestId];
		$mailManager->send('rbs_user_account_request', $website, $LCID, $email, $params);
		$urlManager->absoluteUrl($old);
	}

	/**
	 * @param \Rbs\Mail\MailManager $mailManager
	 * @param string $email
	 * @param integer $requestId
	 * @param \Rbs\Website\Documents\Website $website
	 * @param array $params
	 */
	public function sendMailAccountConfirmed(\Rbs\Mail\MailManager $mailManager, $email, $requestId, \Rbs\Website\Documents\Website $website, $params = [])
	{
		$LCID = isset($params['LCID']) ? $params['LCID'] : $website->getCurrentLCID();
		$urlManager = $website->getUrlManager($LCID);
		$old = $urlManager->absoluteUrl(true);

		$uri = $urlManager->getByFunction('Rbs_User_Login');
		$params['link'] = $uri ? $uri->normalize()->toString() : '';

		$params += ['website' => $website->getTitle(), 'email' => $email, 'requestId' => $requestId];
		$mailManager->send('rbs_user_account_valid', $website, $LCID, $email, $params);
		$urlManager->absoluteUrl($old);
	}

	/**
	 * @param \Rbs\Mail\MailManager $mailManager
	 * @param string $email
	 * @param string $token
	 * @param \Rbs\Website\Documents\Website $website
	 * @param array $params
	 */
	public function sendMailResetPasswordRequest(\Rbs\Mail\MailManager $mailManager, $email, $token, \Rbs\Website\Documents\Website $website, $params = [])
	{
		$LCID = isset($params['LCID']) ? $params['LCID'] : $website->getCurrentLCID();
		$urlManager = $website->getUrlManager($LCID);
		$old = $urlManager->absoluteUrl(true);

		if (!isset($params['link']))
		{
			$location = $urlManager->getByFunction('Rbs_User_ResetPassword', ['token' => $token]);
			$params['link'] = $location ? $location->normalize()->toString(): null;
		}

		$params += ['website' => $website->getTitle(), 'email' => $email, 'token' => $token];
		$mailManager->send('rbs_user_reset_password_request', $website, $LCID, $email, $params);

		$urlManager->absoluteUrl($old);
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param string $email
	 * @return boolean
	 */
	public function userEmailExists(\Change\Documents\DocumentManager $documentManager, $email)
	{
		$dqb = $documentManager->getNewQuery('Rbs_User_User');
		$dqb->andPredicates($dqb->eq('email', $email));
		$count = $dqb->getCountDocuments();
		return ($count !== 0);
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param string $email
	 * @param \DateTime $requestDate
	 * @return integer|null
	 */
	public function accountRequestExists(\Change\Db\DbProvider $dbProvider, $email, \DateTime $requestDate = null)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('request_id'));
		$qb->from($fb->table('rbs_user_account_request'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('email'), $fb->parameter('email')),
			$fb->gt($fb->column('request_date'), $fb->dateTimeParameter('requestDate'))
		));
		$qb->orderDesc($fb->column('request_date')); // Define an order to get the last request.
		$sq = $qb->query();

		$sq->bindParameter('email', $email);
		$sq->bindParameter('requestDate', $requestDate ? $requestDate : new \DateTime());
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('request_id')->singleColumn('request_id'));
	}

	/**
	 * @param \Change\Documents\DocumentManager $documentManager
	 * @param string $password
	 * @return array
	 */
	public function encodeUserPassword(\Change\Documents\DocumentManager $documentManager, $password)
	{
		// Create an unsaved user to get the password hash and the hash method.
		/* @var $user \Rbs\User\Documents\User */
		$user = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_User');
		$user->setPassword($password);
		$parameters = [
			'passwordHash' => $user->getPasswordHash(),
			'hashMethod' => $user->getHashMethod()
		];
		return $parameters;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param string $email
	 * @param array $parameters
	 * @param \DateTime $validRequestDate
	 * @return integer
	 */
	protected function insertAccountRequest(\Change\Db\DbProvider $dbProvider, $email, array $parameters = [], \DateTime $validRequestDate = null)
	{
		$qb = $dbProvider->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		if (!$validRequestDate)
		{
			$validRequestDate = new \DateTime();
			$validRequestDate->add(new \DateInterval('PT24H'));
		}
		$qb->insert($fb->table('rbs_user_account_request'));
		$qb->addColumns($fb->column('email'), $fb->column('config_parameters'), $fb->column('request_date'));
		$qb->addValues($fb->parameter('email'), $fb->parameter('configParameters'), $fb->dateTimeParameter('requestDate'));
		$iq = $qb->insertQuery();

		$iq->bindParameter('email', $email);
		$iq->bindParameter('configParameters', json_encode($parameters));
		$iq->bindParameter('requestDate', $validRequestDate);
		$iq->execute();
		return intval($dbProvider->getLastInsertId('rbs_user_account_request'));
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $requestId
	 * @param string $email
	 * @return array|null
	 */
	public function loadAccountRequestParameters(\Change\Db\DbProvider $dbProvider, $requestId, $email)
	{
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
		$sq->bindParameter('validityDate', new \DateTime());
		$configParameters = $sq->getFirstResult($sq->getRowsConverter()->addTxtCol('config_parameters')->singleColumn('config_parameters'));
		if ($configParameters)
		{
			return json_decode($configParameters, true);
		}
		return null;
	}

	/**
	 * @param \Change\Db\DbProvider $dbProvider
	 * @param integer $userId
	 * @param string $token
	 * @param \DateTime $validRequestDate
	 * @return integer
	 */
	protected function insertResetPasswordRequest(\Change\Db\DbProvider $dbProvider, $userId, $token, \DateTime $validRequestDate = null)
	{
		if (!$validRequestDate)
		{
			$validRequestDate = new \DateTime();
			$validRequestDate->add(new \DateInterval('PT24H'));
		}

		$qb = $dbProvider->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();

		$qb->insert($fb->table('rbs_user_reset_password'));
		$qb->addColumns($fb->column('user_id'), $fb->column('token'), $fb->column('request_date'));
		$qb->addValues($fb->parameter('user_id'), $fb->parameter('token'), $fb->dateTimeParameter('requestDate'));
		$iq = $qb->insertQuery();

		$iq->bindParameter('user_id', $userId);
		$iq->bindParameter('token', $token);
		$iq->bindParameter('requestDate', $validRequestDate);
		$iq->execute();
		$requestId = intval($dbProvider->getLastInsertId('rbs_user_account_request'));
		return $requestId;
	}

	/**
	 * @param integer|\Rbs\User\Documents\User $user
	 * @param array $context
	 * @return array|mixed
	 */
	public function getUserData($user, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['user' => $user, 'context' => $context]);
		$em->trigger('getUserData', $this, $eventArgs);
		if (isset($eventArgs['userData']))
		{
			$userData = $eventArgs['userData'];
			if (is_object($userData))
			{
				$callable = [$userData, 'toArray'];
				if (is_callable($callable))
				{
					$userData = call_user_func($callable);
				}
			}
			if (is_array($userData))
			{
				return $userData;
			}
		}
		return [];
	}

	/**
	 * Input params: user, context
	 * Output param: userData
	 * @param \Change\Events\Event $event
	 */
	public function  onDefaultGetUserData(\Change\Events\Event $event)
	{
		if (!$event->getParam('userData'))
		{
			$userDataComposer = new \Rbs\User\UserDataComposer($event);
			$event->setParam('userData', $userDataComposer->toArray());
		}
	}

	/**
	 * @param integer|\Rbs\User\Documents\User $user
	 * @param array $context
	 */
	public function setUserData($user, array $context)
	{
		$em = $this->getEventManager();
		$eventArgs = $em->prepareArgs(['user' => $user, 'context' => $context]);
		$em->trigger('setUserData', $this, $eventArgs);
	}

	/**
	 * Input params: user, context
	 * @param \Change\Events\Event $event
	 */
	public function  onDefaultSetUserData(\Change\Events\Event $event)
	{
		$context = $event->getParam('context', []) + ['data' => [], 'useFullNameAsDefaultPseudonym' => true];
		$userData = $context['data'];
		$hasUserProfileData = isset($userData['profiles']['Rbs_User']) && is_array($userData['profiles']['Rbs_User']);
		$hasWebsiteProfileData = isset($userData['profiles']['Rbs_User']) && is_array($userData['profiles']['Rbs_User']);
		if ($userData && ($hasUserProfileData || $hasWebsiteProfileData))
		{
			$user = $event->getParam('user');
			if (is_numeric($user))
			{
				$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($user);
			}
			if ($user instanceof \Rbs\User\Documents\User)
			{
				$user = new \Rbs\User\Events\AuthenticatedUser($user);
			}
			if ($user instanceof \Change\User\UserInterface)
			{
				// Rbs_User profile.
				if ($hasUserProfileData)
				{
					$userProfileData = $userData['profiles']['Rbs_User'];
					$profileManager = $event->getApplicationServices()->getProfileManager();
					$userProfile = $profileManager->loadProfile($user, 'Rbs_User');
					if (array_key_exists('titleCode', $userProfileData))
					{
						$value = trim(strval($userProfileData['titleCode']));
						$userProfile->setPropertyValue('titleCode', $value);
					}
					if (array_key_exists('firstName', $userProfileData))
					{
						$value = trim(strval($userProfileData['firstName']));
						$userProfile->setPropertyValue('firstName', $value);
					}
					if (array_key_exists('lastName', $userProfileData))
					{
						$value = trim(strval($userProfileData['lastName']));
						$userProfile->setPropertyValue('lastName', $value);
					}
					if (array_key_exists('phone', $userProfileData))
					{
						$value = trim(strval($userProfileData['phone']));
						$userProfile->setPropertyValue('phone', $value);
					}

					if (array_key_exists('birthDate', $userProfileData))
					{
						$value = trim(strval($userProfileData['birthDate']));
						if ($value)
						{
							$userProfile->setPropertyValue('birthDate', (new \DateTime($value))->format('Y-m-d'));
						}
						else
						{
							$userProfile->setPropertyValue('birthDate', null);
						}
					}
					$profileManager->saveProfile($user, $userProfile);
				}

				// Rbs_Website profile.
				if ($hasWebsiteProfileData)
				{
					$webProfileData = $userData['profiles']['Rbs_Website'];
					$profileManager = $event->getApplicationServices()->getProfileManager();
					$webProfile = $profileManager->loadProfile($user, 'Rbs_Website');
					if (array_key_exists('pseudonym', $webProfileData))
					{
						$value = trim(strval($webProfileData['pseudonym']));
						$webProfile->setPropertyValue('pseudonym', $value);
					}

					if ($context['useFullNameAsDefaultPseudonym'] && !$webProfile->getPropertyValue('pseudonym'))
					{
						if (!isset($userProfile))
						{
							$userProfile = $profileManager->loadProfile($user, 'Rbs_User');
						}
						$value = $userProfile->getPropertyValue('fullName');
						$webProfile->setPropertyValue('pseudonym', $value);
					}
					$profileManager->saveProfile($user, $webProfile);
				}
			}
		}
	}
} 