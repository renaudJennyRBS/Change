<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\ProfileManager;

use Change\User\ProfileManager;
use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\ProfileManager\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach([ProfileManager::EVENT_LOAD], [$this, 'onLoad'], 5);
		$events->attach([ProfileManager::EVENT_SAVE], [$this, 'onSave'], 5);
		$events->attach([ProfileManager::EVENT_PROFILES], [$this, 'onProfiles'], 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param Event $event
	 */
	public function onLoad(Event $event)
	{
		$profileName = $event->getParam('profileName');
		if ($profileName === 'Change_User')
		{
			$profile = new \Change\User\UserProfile();
		}
		elseif ($profileName === 'Rbs_Admin')
		{
			$profile = new \Rbs\Admin\Profile\Profile();
		}
		elseif ($profileName === 'Rbs_User')
		{
			$profile = new \Rbs\User\Profile\Profile();
		}
		elseif ($profileName === 'Rbs_Website')
		{
			$profile = new \Rbs\Website\Profile\Profile();
		}
		else
		{
			return;
		}

		$user = $event->getParam('user');
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices && $user instanceof \Change\User\UserInterface)
		{
			$docUser = $applicationServices->getDocumentManager()->getDocumentInstance($user->getId());
			if ($docUser instanceof \Rbs\User\Documents\User)
			{
				$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_Profile');
				$query->andPredicates($query->eq('user', $docUser));

				$documentProfile = $query->getFirstDocument();
				if ($documentProfile instanceof \Rbs\User\Documents\Profile)
				{
					if ($profileName === 'Change_User' && $documentProfile->getHasChangeUser())
					{
						$profile->setPropertyValue('LCID', $documentProfile->getDefaultLCID());
						$profile->setPropertyValue('TimeZone', $documentProfile->getDefaultTimeZone());
					}
					elseif ($profileName === 'Rbs_Admin' && $documentProfile->getHasRbsAdmin())
					{
						$profile->setPropertyValue('avatar', $documentProfile->getAdminAvatar());
						$profile->setPropertyValue('pagingSize', $documentProfile->getPagingSize());
						$profile->setPropertyValue('documentListViewMode', $documentProfile->getDocumentListViewMode());
						$profile->setPropertyValue('sendNotificationMailImmediately', $documentProfile->getSendNotificationMailImmediately());
						$profile->setPropertyValue('notificationMailInterval', $documentProfile->getNotificationMailInterval());
						$profile->setPropertyValue('notificationMailAt', $documentProfile->getNotificationMailAt());
						$profile->setPropertyValue('dateOfLastNotificationMailSent', $documentProfile->getDateOfLastNotificationMailSent());
					}
					elseif ($profileName === 'Rbs_User' && $documentProfile->getHasRbsUser())
					{
						$profile->setPropertyValue('firstName', $documentProfile->getFirstName());
						$profile->setPropertyValue('lastName', $documentProfile->getLastName());
						$profile->setPropertyValue('titleCode', $documentProfile->getTitleCode());
						$profile->setPropertyValue('birthDate', $documentProfile->getBirthDate());
					}
					elseif ($profileName === 'Rbs_Website' && $documentProfile->getHasRbsWebsite())
					{
						$profile->setPropertyValue('pseudonym', $documentProfile->getWebPseudonym());
					}
				}
			}
		}
		$event->setParam('profile', $profile);
	}

	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function onSave(Event $event)
	{
		$profile = $event->getParam('profile');
		if (!($profile instanceof \Change\User\ProfileInterface))
		{
			return;
		}
		$profileName = $profile->getName();
		if (!in_array($profileName, ['Change_User', 'Rbs_Admin', 'Rbs_User', 'Rbs_Website']))
		{
			return;
		}

		$user = $event->getParam('user');
		$applicationServices = $event->getApplicationServices();
		if ($applicationServices && $user instanceof \Change\User\UserInterface)
		{
			$transactionManager = $applicationServices->getTransactionManager();
			try
			{
				$transactionManager->begin();
				$docUser = $applicationServices->getDocumentManager()->getDocumentInstance($user->getId());
				if ($docUser instanceof \Rbs\User\Documents\User)
				{
					$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_Profile');
					$query->andPredicates($query->eq('user', $docUser));

					/* @var $documentProfile \Rbs\User\Documents\Profile */
					$documentProfile = $query->getFirstDocument();
					if ($documentProfile === null)
					{
						$documentProfile = $applicationServices->getDocumentManager()
							->getNewDocumentInstanceByModelName('Rbs_User_Profile');
						$documentProfile->setUser($docUser);
					}

					if ($profileName === 'Change_User')
					{
						$documentProfile->setHasChangeUser(true);
						$documentProfile->setDefaultLCID($profile->getPropertyValue('LCID'));
						$documentProfile->setDefaultTimeZone($profile->getPropertyValue('timeZone'));
					}
					elseif ($profileName === 'Rbs_Admin')
					{
						$documentProfile->setHasRbsAdmin(true);
						$documentProfile->setAdminAvatar($profile->getPropertyValue('avatar'));
						$documentProfile->setPagingSize($profile->getPropertyValue('pagingSize'));
						$documentProfile->setDocumentListViewMode($profile->getPropertyValue('documentListViewMode'));
						$documentProfile->setSendNotificationMailImmediately($profile->getPropertyValue('sendNotificationMailImmediately'));
						$documentProfile->setNotificationMailInterval($profile->getPropertyValue('notificationMailInterval'));
						$documentProfile->setNotificationMailAt($profile->getPropertyValue('notificationMailAt'));
						$documentProfile->setDateOfLastNotificationMailSent($profile->getPropertyValue('dateOfLastNotificationMailSent'));
					}
					elseif ($profileName === 'Rbs_User')
					{
						$documentProfile->setHasRbsUser(true);
						$documentProfile->setFirstName($profile->getPropertyValue('firstName'));
						$documentProfile->setLastName($profile->getPropertyValue('lastName'));
						$documentProfile->setTitleCode($profile->getPropertyValue('titleCode'));
						$birthDate = trim($profile->getPropertyValue('birthDate'));
						if (!$birthDate)
						{
							$birthDate = null;
						}
						$documentProfile->setBirthDate($birthDate);
					}
					elseif ($profileName === 'Rbs_Website')
					{
						$documentProfile->setHasRbsWebsite(true);
						$documentProfile->setWebPseudonym($profile->getPropertyValue('pseudonym'));
					}

					$documentProfile->save();
				}
				$transactionManager->commit();
			}
			catch (\Exception $e)
			{
				throw $transactionManager->rollBack($e);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onProfiles(Event $event)
	{
		$profiles = $event->getParam('profiles', []);
		$profiles = ['Change_User', 'Rbs_User', 'Rbs_Admin', 'Rbs_Website'] + $profiles;
		$event->setParam('profiles', $profiles);
	}
}