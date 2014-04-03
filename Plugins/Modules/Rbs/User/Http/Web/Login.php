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
}