<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\User;

/**
* @name \Rbs\Commerce\User\ProfileManagerEvents
*/
class ProfileManagerEvents
{
	/**
	 * @var integer
	 */
	protected $userId = null;

	/**
	 * @var \Rbs\Storeshipping\User\Profile
	 */
	protected $storeShippingProfile;

	/**
	 * @var \Rbs\Commerce\Std\Profile
	 */
	protected $commerceProfile;


	public function onProfiles(\Change\Events\Event $event)
	{
		$profiles = $event->getParam('profiles', []);
		$profiles[] = 'Rbs_Commerce';
		$profiles[] = 'Rbs_Storeshipping';
		$event->setParam('profiles', $profiles);
	}

	/**
	 * @param \Change\User\UserInterface $user
	 * @return boolean
	 */
	protected function setUser($user)
	{
		if ($user instanceof \Change\User\UserInterface)
		{
			$userId = $user->authenticated() ? $user->getId() : 0;
			if ($this->userId !== $userId)
			{
				$this->userId = $userId;
				$this->storeShippingProfile = null;
				$this->commerceProfile = null;
			}
			return true;
		}
		return false;
	}

	public function onLoad(\Change\Events\Event $event)
	{
		$profileName = $event->getParam('profileName');
		if ($profileName === 'Rbs_Commerce')
		{
			$user = $event->getParam('user');
			if ($this->setUser($user))
			{
				if (!$this->commerceProfile)
				{
					$event->getApplication()->getLogging()->info('Load commerce profile for user: '. $this->userId);
					$profile = new \Rbs\Commerce\Std\Profile();
					$profile->setUserId($this->userId);
					if ($this->userId)
					{
						$applicationServices = $event->getApplicationServices();
						$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Commerce_Profile');
						$query->andPredicates($query->eq('user', $profile->getUserId()));
						$documentProfile = $query->getFirstDocument();
						if ($documentProfile instanceof \Rbs\Commerce\Documents\Profile)
						{
							$profile->setDefaultBillingAddressId($documentProfile->getDefaultBillingAddressId());
							$profile->setDefaultShippingAddressId($documentProfile->getDefaultShippingAddressId());
							$profile->setDefaultWebStoreId($documentProfile->getDefaultWebStoreId());
						}
					}
					$this->commerceProfile = $profile;
				}
				else
				{
					$event->getApplication()->getLogging()->info('Get commerce profile for user: '. $this->userId);
					$profile = $this->commerceProfile;
				}
			}
			else
			{
				$profile = new \Rbs\Commerce\Std\Profile();
			}
			$event->setParam('profile', $profile);
		}
		elseif ($profileName === 'Rbs_Storeshipping')
		{
			/** @var \Change\User\UserInterface $user */
			$user = $event->getParam('user');
			if ($this->setUser($user))
			{
				if (!$this->storeShippingProfile)
				{
					$event->getApplication()->getLogging()->info('Load Storeshipping profile for user: ', $this->userId);
					$profile = new \Rbs\Storeshipping\User\Profile($this->userId);
					if (!$this->loadFromSession($profile))
					{
						if ($this->userId)
						{
							$event->getApplication()->getLogging()->info('loadDbByUserId: ', $profile->getUserId());
							$this->loadDbByUserId($profile, $event->getApplicationServices()->getDbProvider());
						}
						$this->saveToSession($profile);
					}
					$this->storeShippingProfile = $profile;
				}
				else
				{
					$event->getApplication()->getLogging()->info('Get Storeshipping profile for user: '. $this->userId);
					$profile = $this->storeShippingProfile;
				}
			}
			else
			{
				$profile = new \Rbs\Storeshipping\User\Profile();
			}
			$event->setParam('profile', $profile);
		}
		return;
	}

	public function onSave(\Change\Events\Event $event)
	{
		$profile = $event->getParam('profile');
		if ($profile instanceof \Rbs\Commerce\Std\Profile)
		{
			if ($this->setUser($event->getParam('user')))
			{
				$profile->setUserId($this->userId);
				$this->commerceProfile = $profile;
				if ($this->userId)
				{
					$applicationServices = $event->getApplicationServices();
					$transactionManager = $applicationServices->getTransactionManager();
					try
					{
						$transactionManager->begin();
						$documentManager = $applicationServices->getDocumentManager();
						$docUser = $documentManager->getDocumentInstance($this->userId);
						if ($docUser instanceof \Rbs\User\Documents\User)
						{
							$event->getApplication()->getLogging()->info('Save commerce profile for user: '. $this->userId);

							$query = $documentManager->getNewQuery('Rbs_Commerce_Profile');
							$query->andPredicates($query->eq('user', $docUser));

							/* @var $documentProfile \Rbs\Commerce\Documents\Profile */
							$documentProfile = $query->getFirstDocument();
							if ($documentProfile === null)
							{
								$documentProfile = $applicationServices->getDocumentManager()
									->getNewDocumentInstanceByModelName('Rbs_Commerce_Profile');
								$documentProfile->setUser($docUser);
							}

							$webStore = $documentManager->getDocumentInstance($profile->getDefaultWebStoreId());
							$documentProfile->setDefaultWebStore(($webStore instanceof
								\Rbs\Store\Documents\WebStore) ? $webStore : null);

							$address = $documentManager->getDocumentInstance($profile->getDefaultBillingAddressId());
							$documentProfile->setDefaultBillingAddress(($address instanceof
								\Rbs\Geo\Documents\Address) ? $address : null);

							$address = $documentManager->getDocumentInstance($profile->getDefaultShippingAddressId());
							$documentProfile->setDefaultShippingAddress(($address instanceof
								\Rbs\Geo\Documents\Address) ? $address : null);

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
		elseif ($profile instanceof \Rbs\Storeshipping\User\Profile)
		{
			/** @var \Change\User\UserInterface $user */
			if ($this->setUser($event->getParam('user')))
			{
				$profile->setUserId($this->userId);
				$this->storeShippingProfile = $profile;
				if ($this->userId)
				{
					$applicationServices = $event->getApplicationServices();
					$transactionManager = $applicationServices->getTransactionManager();
					$dbProvider = $applicationServices->getDbProvider();
					try
					{
						$transactionManager->begin();

						$event->getApplication()->getLogging()->info('Save Storeshipping profile for user: '. $this->userId);

						$oldProfile = clone $profile;
						$event->getApplication()->getLogging()->info('loadDbByUserId: '. $oldProfile->getUserId());
						$this->loadDbByUserId($oldProfile, $dbProvider);
						$profile->inDb($oldProfile->inDb());
						if (!$profile->inDb() || $profile->getStoreCode() != $oldProfile->getStoreCode())
						{
							$profile->setLastUpdate(new \DateTime());
							$event->getApplication()->getLogging()->info('saveToDb: '. $profile->getUserId());
							$this->saveToDb($profile, $dbProvider);
						}

						$transactionManager->commit();
					}
					catch (\Exception $e)
					{
						$event->getApplication()->getLogging()->exception($e);
						$transactionManager->rollBack($e);
					}
				}

				$this->saveToSession($profile);
			}
		}
	}


	/**
	 * @param \Rbs\Storeshipping\User\Profile $profile
	 * @return boolean
	 */
	protected function loadFromSession($profile)
	{
		$session = new \Zend\Session\Container('Rbs_Storeshipping');
		$profileData = isset($session['profile']) ? $session['profile'] : [];
		if (is_array($profileData) && isset($profileData['userId']) && $profileData['userId'] == $profile->getUserId())
		{
			$profileData += ['inDb' => false, 'storeCode' => null, 'coordinates' => null, 'locationAddress' => null, 'lastUpdate' => null];
			$profile->inDb($profileData['inDb']);
			$profile->setStoreCode($profileData['storeCode']);
			$profile->setCoordinates($profileData['coordinates']);
			$profile->setLocationAddress($profileData['locationAddress']);
			$profile->setLastUpdate($profileData['lastUpdate']);
			return true;
		}
		return false;
	}

	/**
	 * @param \Rbs\Storeshipping\User\Profile $profile
	 */
	protected function saveToSession($profile)
	{
		$session = new \Zend\Session\Container('Rbs_Storeshipping');
		$profileData = ['userId' => $profile->getUserId(), 'inDb' => $profile->inDb(),
			'storeCode' => $profile->getStoreCode(),
			'coordinates' => $profile->getCoordinates(),
			'locationAddress' => $profile->getLocationAddress(),
			'lastUpdate' => $profile->getLastUpdate()];
		$session['profile'] = $profileData;
	}

	/**
	 * @param \Rbs\Storeshipping\User\Profile $profile
	 * @param \Change\Db\DbProvider $dbProvider
	 */
	protected function loadDbByUserId($profile, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder('Rbs_Storeshipping::loadDbByUserId');
		if (!$qb->isCached()) {
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('store_code'), $fb->column('coordinates'), $fb->column('location_address'), $fb->column('last_update'));
			$qb->from($fb->table('rbs_storeshipping_dat_profile'));
			$qb->where($fb->logicAnd($fb->eq($fb->column('user_id'), $fb->integerParameter('userId'))));
		}
		$select = $qb->query();
		$select->bindParameter('userId', $profile->getUserId());
		$data = $select->getFirstResult($select->getRowsConverter()->addStrCol('store_code', 'coordinates', 'location_address')->addDtCol('last_update'));

		if (is_array($data) && count($data))
		{
			$profile
				->setLastUpdate($data['last_update'])
				->setStoreCode($data['store_code'])
				->setCoordinates($data['coordinates'])
				->setLocationAddress($data['location_address'])
				->inDb(true);
		}
		else
		{
			$profile->inDb(false);
		}
	}

	/**
	 * @param \Rbs\Storeshipping\User\Profile $profile
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
				$qb->assign($fb->column('coordinates'),  $fb->parameter('coordinates'));
				$qb->assign($fb->column('location_address'),  $fb->parameter('locationAddress'));
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
					$fb->column('store_code'), $fb->column('coordinates'), $fb->column('locationAddress'),
					$fb->column('last_update'), $fb->column('user_id'));
				$qb->addValues($fb->parameter('storeCode'), $fb->parameter('coordinates'), $fb->parameter('locationAddress'),
					$fb->dateTimeParameter('lastUpdate'), $fb->integerParameter('userId'));
			}
			$stmt = $qb->insertQuery();
		}

		$stmt->bindParameter('storeCode', $profile->getStoreCode());
		$stmt->bindParameter('coordinates', $profile->getCoordinates());
		$stmt->bindParameter('locationAddress', $profile->getLocationAddress());
		$stmt->bindParameter('lastUpdate', $profile->getLastUpdate());
		$stmt->bindParameter('userId', $profile->getUserId());
		$stmt->execute();

		$profile->inDb(true);
	}
}