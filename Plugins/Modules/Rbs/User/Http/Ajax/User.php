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
* @name \Rbs\User\Http\Ajax\User
*/
class User
{
	/**
	 * Default actionPath: Rbs/User/User/AccountRequest
	 * Event params:
	 *  - website
	 *  - data:
	 *     - email
	 *     - password
	 *     - ...
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function createAccountRequest(\Change\Http\Event $event)
	{
		$website = $event->getParam('website');
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['email']) && isset($data['password']) && $website instanceof \Rbs\Website\Documents\Website)
		{
			$email = trim(strval($data['email']));
			$data['websiteId'] = $website->getId();
			$data['LCID'] = $website->getCurrentLCID();

			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$result = $genericServices->getUserManager()->createAccountRequest($email, $data);
			if ($result)
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/User/AccountRequest', ['common' => ['email' => $email]]);
				$event->setResult($result);
			}
			else
			{
				$this->setErrorResult($event, 'CreateAccountRequestError', $genericServices->getUserManager()->getLastError() ,
					\Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$i18nManager = $event->getApplicationServices()->getI18nManager();
			$this->setErrorResult($event, 'Bad Request',  $i18nManager->trans('m.rbs.user.front.bad_request', ['ucf']));
		}
	}

	/**
	 * Default actionPath: Rbs/User/User/AccountRequest
	 * Event params:
	 *  - website
	 *  - data:
	 *     - requestId
	 *     - email
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function confirmAccountRequest(\Change\Http\Event $event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$website = $event->getParam('website');
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['requestId']) && isset($data['email']) && $website instanceof \Rbs\Website\Documents\Website)
		{
			$email = strval($data['email']);
			$requestId = intval($data['requestId']);

			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$user = $genericServices->getUserManager()->confirmAccountRequest($requestId, $email);
			if ($user)
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/User/AccountRequest', ['common' => ['email' => $email, 'id' => $user->getId()]]);
				$event->setResult($result);
			}
			else
			{
				$this->setErrorResult($event, 'ConfirmAccountRequestError', $genericServices->getUserManager()->getLastError() ,
					\Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$this->setErrorResult($event, 'Bad Request',  $i18nManager->trans('m.rbs.user.front.bad_request', ['ucf']));
		}
	}

	/**
	 * Default actionPath: Rbs/User/User/ResetPasswordRequest
	 * Event params:
	 *  - website
	 *  - data:
	 *     - email
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function createResetPasswordRequest(\Change\Http\Event $event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$website = $event->getParam('website');
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['email']) && $website instanceof \Rbs\Website\Documents\Website)
		{
			$email = trim(strval($data['email']));
			$data['websiteId'] = $website->getId();
			$data['LCID'] = $website->getCurrentLCID();

			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$result = $genericServices->getUserManager()->createResetPasswordRequest($email, $data);
			if ($result)
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/User/ResetPasswordRequest', ['common' => ['email' => $email]]);
				$event->setResult($result);
			}
			else
			{
				$this->setErrorResult($event, 'CreateResetPasswordRequestError', $genericServices->getUserManager()->getLastError(),
					\Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$this->setErrorResult($event, 'Bad Request',  $i18nManager->trans('m.rbs.user.front.bad_request', ['ucf']));
		}
	}

	/**
	 * Default actionPath: Rbs/User/User/ResetPasswordRequest
	 * Event params:
	 *  - website
	 *  - data:
	 *     - token
	 *     - password
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function confirmResetPasswordRequest(\Change\Http\Event $event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$website = $event->getParam('website');
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['token']) && isset($data['password']) && $website instanceof \Rbs\Website\Documents\Website)
		{
			$token = strval($data['token']);
			$password = strval($data['password']);

			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$user = $genericServices->getUserManager()->confirmResetPasswordRequest($token, $password);
			if ($user)
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/User/ResetPasswordRequest', ['common' => ['id' => $user->getId()]]);
				$event->setResult($result);
			}
			else
			{
				$this->setErrorResult($event, 'ConfirmResetPasswordRequest', $genericServices->getUserManager()->getLastError() ,
					\Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$this->setErrorResult($event, 'Bad Request',  $i18nManager->trans('m.rbs.user.front.bad_request', ['ucf']));
		}
	}

	/**
	 * Default actionPath: Rbs/User/User/ChangePassword
	 * Event params:
	 *  - website
	 *  - data:
	 *     - currentPassword
	 *     - password
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function changePassword(\Change\Http\Event $event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$website = $event->getParam('website');
		$data = $event->getParam('data');
		if (is_array($data) && isset($data['currentPassword']) && isset($data['password']) && $website instanceof \Rbs\Website\Documents\Website)
		{
			$currentPassword = strval($data['currentPassword']);
			$password = strval($data['password']);

			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$user = $genericServices->getUserManager()->changePassword($currentPassword, $password);
			if ($user)
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/User/ChangePassword', ['common' => ['id' => $user->getId()]]);
				$event->setResult($result);
			}
			else
			{
				$this->setErrorResult($event, 'ChangePassword', $genericServices->getUserManager()->getLastError() ,
					\Zend\Http\Response::STATUS_CODE_409);
			}
		}
		else
		{
			$this->setErrorResult($event, 'Bad Request',  $i18nManager->trans('m.rbs.user.front.bad_request', ['ucf']));
		}
	}

	/**
	 * Default actionPath: Rbs/User/User/Profiles
	 * Event params:
	 *  - website
	 *  - data:
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function getProfiles(\Change\Http\Event $event)
	{
		$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if ($currentUser->authenticated())
		{
			$event->setParam('detailed', true);
			$context = $event->paramsToArray();
			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$userData = $genericServices->getUserManager()->getUserData($currentUser->getId(), $context);
			if ($userData)
			{
				$result = new \Change\Http\Ajax\V1\ItemResult('Rbs/User/User/Profiles', $userData);
				$event->setResult($result);
			}
		}
	}

	/**
	 * Default actionPath: Rbs/User/User/Profiles
	 * Event params:
	 *  - website
	 *  - data:
	 * @param \Change\Http\Event $event
	 * @throws \Change\Transaction\RollbackException
	 * @throws \Exception
	 */
	public function setProfiles(\Change\Http\Event $event)
	{
		$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if ($currentUser->authenticated())
		{
			$context = $event->paramsToArray();
			/* @var \Rbs\Generic\GenericServices $genericServices */
			$genericServices = $event->getServices('genericServices');
			$genericServices->getUserManager()->setUserData($currentUser->getId(), $context);
			$this->getProfiles($event);
		}
	}


	/**
	 * @param \Change\Http\Event $event
	 * @param string $code
	 * @param string$message
	 * @param integer $httpStatus
	 * @return \Change\Http\Ajax\V1\ErrorResult
	 */
	public function setErrorResult(\Change\Http\Event $event, $code, $message, $httpStatus = \Zend\Http\Response::STATUS_CODE_400)
	{
		$errorResult = new \Change\Http\Ajax\V1\ErrorResult($code, $message, $httpStatus);
		$event->setResult($errorResult);
		return $errorResult;
	}
} 