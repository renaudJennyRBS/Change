<?php
/**
 * Copyright (C) 2014 Proximis
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User;

/**
 * @name \Rbs\User\UserDataComposer
 */
class UserDataComposer
{
	use \Change\Http\Ajax\V1\Traits\DataComposer;

	/**
	 * @var \Rbs\User\Documents\User
	 */
	protected $user;

	/**
	 * @var \Change\User\ProfileManager
	 */
	protected $profileManager;

	/**
	 * @var \Change\Collection\CollectionManager
	 */
	protected $collectionManager;

	/**
	 * @var array
	 */
	protected $dataSets;

	function __construct(\Change\Events\Event $event)
	{

		$context = $event->getParam('context');
		$this->setContext(is_array($context) ? $context : []);
		$applicationServices = $event->getApplicationServices();
		$this->setServices($applicationServices);
		$this->profileManager = $applicationServices->getProfileManager();
		$this->collectionManager = $applicationServices->getCollectionManager();

		$user = $event->getParam('user');
		if (is_numeric($user))
		{
			$user = $this->documentManager->getDocumentInstance($user);
		}

		if ($user instanceof \Rbs\User\Documents\User)
		{
			$this->user = $user;
		}
	}

	public function toArray()
	{
		if ($this->dataSets === null)
		{
			$this->generateDataSets();
		}
		return $this->dataSets;
	}

	protected function generateDataSets()
	{
		if (!$this->user)
		{
			$this->dataSets = [];
			return;
		}

		$this->dataSets = [
			'common' => [
				'id' => $this->user->getId(),
				'login' => $this->user->getLogin(),
				'email' => $this->user->getEmail(),
			],
		];

		if ($this->detailed || $this->hasDataSet('profiles'))
		{
			$this->generateProfilesDataSet();
		}

		if ($this->detailed || $this->hasDataSet('groups'))
		{
			$this->generateGroupsDataSet();
		}
	}

	protected function generateProfilesDataSet()
	{
		$this->dataSets['profiles'] = [];
		$user = new \Rbs\User\Events\AuthenticatedUser($this->user);
		$profileNames = $this->profileManager->getProfileNames();
		foreach ($profileNames as $profileName)
		{
			$profile = $this->profileManager->loadProfile($user, $profileName);
			if ($profile instanceof \Change\User\ProfileInterface)
			{
				$profileData = $this->getProfileDataSet($profile);
				if ($profileName == 'Rbs_User')
				{
					$this->dataSets['profiles'][$profileName] = $this->updateRbsUserProfile($profileData);
				}
				else
				{
					$this->dataSets['profiles'][$profileName] = $profileData;
				}
			}
		}
	}

	/**
	 * @param \Change\User\ProfileInterface $profile
	 * @return array
	 */
	protected function getProfileDataSet(\Change\User\ProfileInterface $profile)
	{
		$profileData = [];
		foreach ($profile->getPropertyNames() as $propertyName)
		{
			$value = $profile->getPropertyValue($propertyName);
			if ($value instanceof \DateTime)
			{
				$value = $this->formatDate($value);
			}
			elseif ($value instanceof \Change\Documents\AbstractDocument)
			{
				$value = ['id' => $value->getId(), 'model' => $value->getDocumentModelName()];
			}
			$profileData[$propertyName] = $value;
		}

		return $profileData;
	}

	protected function generateGroupsDataSet()
	{
		$this->dataSets['groups'] = [];
		foreach ($this->user->getGroups() as $group)
		{
			$this->dataSets['groups'][] = ['id' => $group->getId(), 'realm' => $group->getRealm()];
		}
	}

	/**
	 * @param array $profileData
	 * @return array
	 */
	protected function updateRbsUserProfile(array $profileData)
	{
		$profileData['titleCodeTitle'] = $profileData['titleCode'];
		$collectionManager = $this->collectionManager;
		$collection = $collectionManager->getCollection('Rbs_User_Collection_Title');
		if ($collection)
		{
			if ($profileData['titleCode'])
			{
				$item = $collection->getItemByValue($profileData['titleCode']);
				if ($item != null)
				{
					$profileData['titleCodeTitle'] = $item->getTitle();
				}
			}

			foreach ($collection->getItems() as $tmp)
			{
				$profileData['allowedTitles'][] = ['title' => $tmp->getTitle(), 'value' => $tmp->getValue()];
			}
		}

		return $profileData;
	}
}