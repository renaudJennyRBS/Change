<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Std;

use Change\User\AbstractProfile;

/**
 * @name \Rbs\Commerce\Std\Profile
 */
class Profile extends AbstractProfile
{
	protected $userId = 0;

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'Rbs_Commerce';
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
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('defaultWebStoreId', 'defaultBillingAddressId', 'defaultShippingAddressId');
	}

	/**
	 * @param \Rbs\Store\Documents\WebStore $defaultWebStore
	 * @return $this
	 */
	public function setDefaultWebStore($defaultWebStore)
	{
		if ($defaultWebStore instanceof \Rbs\Store\Documents\WebStore)
		{
			$this->properties['defaultWebStoreId'] = $defaultWebStore->getId();
		}
		else
		{
			$this->properties['defaultWebStoreId'] = null;
		}

		return $this;
	}

	/**
	 * @param integer $defaultWebStoreId
	 * @return $this
	 */
	public function setDefaultWebStoreId($defaultWebStoreId)
	{
		$this->properties['defaultWebStoreId'] = $defaultWebStoreId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getDefaultWebStoreId()
	{
		return isset($this->properties['defaultWebStoreId']) ? $this->properties['defaultWebStoreId'] : null;
	}

	/**
	 * @param integer $defaultBillingAddressId
	 * @return $this
	 */
	public function setDefaultBillingAddressId($defaultBillingAddressId)
	{
		$this->properties['defaultBillingAddressId'] = $defaultBillingAddressId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getDefaultBillingAddressId()
	{
		return isset($this->properties['defaultBillingAddressId']) ? $this->properties['defaultBillingAddressId'] : null;
	}

	/**
	 * @param integer $defaultShippingAddressId
	 * @return $this
	 */
	public function setDefaultShippingAddressId($defaultShippingAddressId)
	{
		$this->properties['defaultShippingAddressId'] = $defaultShippingAddressId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getDefaultShippingAddressId()
	{
		return isset($this->properties['defaultShippingAddressId']) ? $this->properties['defaultShippingAddressId'] : null;
	}
}