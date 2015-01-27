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
* @name \Rbs\Storeshipping\User\Profile
*/
class Profile extends \Change\User\AbstractProfile
{
	/**
	 * @var integer
	 */
	protected $userId;

	/**
	 * @var boolean
	 */
	protected $inDb = false;

	/**
	 * @var \DateTime
	 */
	protected $lastUpdate;

	/**
	 * @param integer $userId
	 */
	function __construct($userId = 0)
	{
		$this->userId = $userId;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_Storeshipping';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('storeCode');
	}

	/**
	 * @param boolean $inDb
	 * @return boolean
	 */
	public function inDb($inDb = null)
	{
		if ($inDb !== null) {
			$this->inDb = ($inDb == true);
		}
		return $this->inDb;
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param int $userId
	 * @return $this
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastUpdate()
	{
		return $this->lastUpdate;
	}

	/**
	 * @param \DateTime $lastUpdate
	 * @return $this
	 */
	public function setLastUpdate($lastUpdate)
	{
		$this->lastUpdate = $lastUpdate;
		return $this;
	}


	/**
	 * @return string|null
	 */
	public function getStoreCode()
	{
		return $this->getPropertyValue('storeCode');
	}

	/**
	 * @param string|null $storeCode
	 * @return $this
	 */
	public function setStoreCode($storeCode)
	{
		$this->setPropertyValue('storeCode', $storeCode);
		$this->setLastUpdate(new \DateTime());
		return $this;
	}
}