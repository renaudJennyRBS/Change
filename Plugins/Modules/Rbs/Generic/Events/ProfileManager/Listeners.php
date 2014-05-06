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
		$events->attach(array(ProfileManager::EVENT_LOAD), array($this, 'onLoad'), 5);
		$events->attach(array(ProfileManager::EVENT_SAVE), array($this, 'onSave'), 5);
		$events->attach(array(ProfileManager::EVENT_PROFILES), array($this, 'onProfiles'), 5);
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
		if ($event->getParam('profileName') === 'Rbs_Admin')
		{
			$profile = new \Rbs\Admin\Profile\Profile();
			$user = $event->getParam('user');
			$applicationServices = $event->getApplicationServices();
			if ($applicationServices && $user instanceof \Change\User\UserInterface)
			{
				$docUser = $applicationServices->getDocumentManager()->getDocumentInstance($user->getId());
				if ($docUser instanceof \Rbs\User\Documents\User)
				{
					$result = $docUser->getMeta('profile_Rbs_Admin');
					if (is_array($result))
					{
						foreach ($result as $name => $value)
						{
							$profile->setPropertyValue($name, $value);
						}
					}
				}
			}
			$event->setParam('profile', $profile);
		}
		else if ($event->getParam('profileName') === 'Rbs_User')
		{
			$profile = new \Rbs\User\Profile\Profile();

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
						$profile->setPropertyValue('fullName', $documentProfile->getFullName());
						$profile->setPropertyValue('titleCode', $documentProfile->getTitleCode());
						$profile->setPropertyValue('birthDate', $documentProfile->getBirthDate());
					}
				}
			}
			$event->setParam('profile', $profile);
		}
		else if ($event->getParam('profileName') === 'Change_User')
		{
			$profile = new \Change\User\UserProfile();

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
						$profile->setPropertyValue('LCID', $documentProfile->getDefaultLCID());
						$profile->setPropertyValue('TimeZone', $documentProfile->getDefaultTimeZone());
					}
				}
			}
			$event->setParam('profile', $profile);
		}
	}

	/**
	 * @param Event $event
	 * @throws \Exception
	 */
	public function onSave(Event $event)
	{
		$profile = $event->getParam('profile');
		if ($profile instanceof \Rbs\Admin\Profile\Profile)
		{
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
						$props = array();
						foreach ($profile->getPropertyNames() as $name)
						{
							$props[$name] = $profile->getPropertyValue($name);
						}
						$docUser->setMeta('profile_Rbs_Admin', $props);
						$docUser->saveMetas();
					}
					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
		else if ($profile instanceof \Rbs\User\Profile\Profile)
		{
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

						$documentProfile->setFullName($profile->getPropertyValue('fullName'));
						$documentProfile->setTitleCode($profile->getPropertyValue('titleCode'));
						$documentProfile->setBirthDate($profile->getPropertyValue('birthDate'));
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
		else if ($profile instanceof \Change\User\UserProfile)
		{
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

						$documentProfile->setDefaultLCID($profile->getLCID());
						$documentProfile->setDefaultTimeZone($profile->getTimeZone());
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
	}

	/**
	 * @param Event $event
	 */
	public function onProfiles(Event $event)
	{
		$profiles = $event->getParam('profiles', []);
		$profiles = ['Change_User', 'Rbs_User', 'Rbs_Admin'] + $profiles;
		$event->setParam('profiles', $profiles);
	}
}