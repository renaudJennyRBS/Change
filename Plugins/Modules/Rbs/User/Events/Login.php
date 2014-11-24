<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Events;

use Change\Events\Event;
use Change\Stdlib\String;

/**
 * @name \Rbs\User\Events\Login
 */
class Login
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		/** @var \Rbs\User\Documents\User|null $user */
		$user = null;

		if ($event->getParam('userId'))
		{
			$user = $applicationServices->getDocumentManager()->getDocumentInstance($event->getParam('userId'));
		}
		else
		{
			$realm = $event->getParam('realm');
			$login = $event->getParam('login');
			$password = $event->getParam('password');
			if (String::isEmpty($realm) ||String::isEmpty($login) || String::isEmpty($password))
			{
				return;
			}

			if ($login === 'RBSCHANGE_AUTOLOGIN' && $realm === 'auto_login')
			{
				$qb = $applicationServices->getDbProvider()->getNewQueryBuilder();
				$fb = $qb->getFragmentBuilder();
				$qb->select($fb->column('user_id'));
				$qb->from($fb->table('rbs_user_auto_login'));
				$qb->where($fb->logicAnd(
					$fb->eq($fb->column('token'), $fb->parameter('token')),
					$fb->gt($fb->column('validity_date'), $fb->dateTimeParameter('validityDate'))
				));
				$sq = $qb->query();

				$sq->bindParameter('token', $password);
				$now = new \DateTime();
				$sq->bindParameter('validityDate', $now);
				$userId = $sq->getFirstResult($sq->getRowsConverter()->addIntCol('user_id'));
				if ($userId)
				{
					$user = $applicationServices->getDocumentManager()->getDocumentInstance($userId);
				}
			}
			else
			{
				$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_User');
				$groupBuilder = $query->getPropertyBuilder('groups');
				$or = $query->getFragmentBuilder()->logicOr($query->eq('login', $login), $query->eq('email', $login));
				$query->andPredicates($query->activated(), $or, $groupBuilder->eq('realm', $realm));

				foreach ($query->getDocuments() as $document)
				{
					/* @var $document \Rbs\User\Documents\User */
					if ($document->checkPassword($password))
					{
						$user = $document;
						break;
					}
				}
			}
		}

		if ($user instanceof \Rbs\User\Documents\User)
		{
			$authenticatedUser = new AuthenticatedUser($user);
			$profile = $event->getApplicationServices()->getProfileManager()->loadProfile($authenticatedUser, 'Rbs_User');
			if ($profile)
			{
				$fullName = $profile->getPropertyValue('fullName');
				if (!String::isEmpty($fullName))
				{
					$authenticatedUser->setName($fullName);
				}
			}
			$event->setParam('user', $authenticatedUser);
			return;
		}
	}
}