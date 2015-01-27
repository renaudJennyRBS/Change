<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\User;

/**
* @name \Rbs\Storeshipping\User\ProfileManagerEvents
*/
class ProfileManagerEvents
{
	protected $profiles = [];

	public function onLoad(\Change\Events\Event $event)
	{
		$profileName = $event->getParam('profileName');
		if ($profileName !== 'Rbs_Storeshipping')
		{
			return;
		}

		/** @var \Change\User\UserInterface $user */
		$user = $event->getParam('user');
		$userId = $user->authenticated() ? $user->getId() : 0;
		if (!isset($this->profiles[$userId]))
		{
			$profile = new Profile($userId);
			if (!$this->loadFromSession($profile))
			{
				if ($userId)
				{
					$event->getApplication()->getLogging()->info('loadDbByUserId: ', $profile->getUserId());
					$this->loadDbByUserId($profile, $event->getApplicationServices()->getDbProvider());
				}
				$this->saveToSession($profile);
			}
			$this->profiles[$userId] = $profile;
		}

		$event->setParam('profile', $this->profiles[$userId]);
		return;
	}

	public function onSave(\Change\Events\Event $event)
	{
		/** @var Profile $profile */
		$profile = $event->getParam('profile');
		if (!($profile instanceof Profile))
		{
			return;
		}
		/** @var \Change\User\UserInterface $user */
		$user = $event->getParam('user');
		$userId = $user->authenticated() ? $user->getId() : 0;
		$applicationServices = $event->getApplicationServices();
		$transactionManager = $applicationServices->getTransactionManager();
		$dbProvider = $applicationServices->getDbProvider();
		try
		{
			$transactionManager->begin();
			$profile->setUserId($userId);

			if ($userId)
			{
				$oldProfile = clone $profile;
				$event->getApplication()->getLogging()->info('loadDbByUserId: ', $oldProfile->getUserId());
				$this->loadDbByUserId($oldProfile, $dbProvider);
				$profile->inDb($oldProfile->inDb());
				if (!$profile->inDb() || $profile->getStoreCode() != $oldProfile->getStoreCode())
				{
					$profile->setLastUpdate(new \DateTime());

					$event->getApplication()->getLogging()->info('saveToDb: ', $profile->getUserId());
					$this->saveToDb($profile, $dbProvider);
				}
			}
			$this->saveToSession($profile);
			$this->profiles[$userId] = $profile;

			$transactionManager->commit();
		}
		catch (\Exception $e)
		{
			$event->getApplication()->getLogging()->exception($e);
			$transactionManager->rollBack($e);
		}
	}

	public function onProfiles(\Change\Events\Event $event)
	{
		$profiles = $event->getParam('profiles', []);
		$profiles[] = 'Rbs_Storeshipping';
		$event->setParam('profiles', $profiles);
	}

	/**
	 * @param Profile $profile
	 * @return boolean
	 */
	protected function loadFromSession($profile)
	{
		$session = new \Zend\Session\Container('Rbs_Storeshipping');
		$profileData = isset($session['profile']) ? $session['profile'] : [];
		if (is_array($profileData) && isset($profileData['userId']) && $profileData['userId'] == $profile->getUserId())
		{
			$profile->inDb(isset($profileData['inDb']) ? $profileData['inDb'] : false);
			$profile->setStoreCode(isset($profileData['storeCode']) ? $profileData['storeCode'] : null);
			$profile->setLastUpdate(isset($profileData['lastUpdate']) ? $profileData['lastUpdate'] : null);
			return true;
		}
		return false;
	}


	/**
	 * @param Profile $profile
	 */
	protected function saveToSession($profile)
	{
		$session = new \Zend\Session\Container('Rbs_Storeshipping');
		$profileData = ['userId' => $profile->getUserId(), 'inDb' => $profile->inDb(),
			'storeCode' => $profile->getStoreCode(), 'lastUpdate' => $profile->getLastUpdate()];
		$session['profile'] = $profileData;
	}

	/**
	 * @param Profile $profile
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function loadDbByUserId($profile, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder('Rbs_Storeshipping::loadDbByUserId');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('store_code'), $fb->column('last_update'));
			$qb->from($fb->table('rbs_storeshipping_dat_profile'));
			$qb->where($fb->logicAnd($fb->eq($fb->column('user_id'), $fb->integerParameter('userId'))));
		}
		$select = $qb->query();
		$select->bindParameter('userId', $profile->getUserId());
		$data = $select->getFirstResult($select->getRowsConverter()->addStrCol('store_code')->addDtCol('last_update'));

		if (is_array($data) && count($data))
		{
			$profile
				->setLastUpdate($data['last_update'])
				->setStoreCode($data['store_code'])
				->inDb(true);
		}
		else
		{
			$profile->inDb(false);
		}
	}

	/**
	 * @param Profile $profile
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function saveToDb($profile, $dbProvider)
	{
		$stmt = null;
		if ($profile->inDb())
		{
			$qb = $dbProvider->getNewStatementBuilder('Rbs_Storeshipping::update');
			if (!$qb->isCached()) {
				$fb = $qb->getFragmentBuilder();
				$qb->update($fb->table('rbs_storeshipping_dat_profile'));
				$qb->assign($fb->column('store_code'), $fb->parameter('storeCode'));
				$qb->assign($fb->column('last_update'), $fb->dateTimeParameter('lastUpdate'));

				$qb->where($fb->logicAnd($fb->eq($fb->column('user_id'), $fb->integerParameter('userId'))));

			}
			$stmt = $qb->updateQuery();
		}
		else
		{
			$qb = $dbProvider->getNewStatementBuilder('Rbs_Storeshipping::insert');
			if (!$qb->isCached())
			{
				$fb = $qb->getFragmentBuilder();
				$qb->insert($fb->table('rbs_storeshipping_dat_profile'),
					$fb->column('store_code'), $fb->column('last_update'), $fb->column('user_id'));
				$qb->addValues($fb->parameter('storeCode'), $fb->dateTimeParameter('lastUpdate'), $fb->integerParameter('userId'));
			}
			$stmt = $qb->insertQuery();
		}

		$stmt->bindParameter('storeCode', $profile->getStoreCode());
		$stmt->bindParameter('lastUpdate', $profile->getLastUpdate());
		$stmt->bindParameter('userId', $profile->getUserId());
		$stmt->execute();

		$profile->inDb(true);
	}
}