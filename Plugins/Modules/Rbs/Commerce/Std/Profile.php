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

	public function getName()
	{
		return 'Rbs_Commerce';
	}

	/**
	 * @return string[]
	 */
	public function getPropertyNames()
	{
		return array('lastCartIdentifier', 'defaultWebStoreId', 'defaultZone', 'defaultBillingAreaId', 'defaultAddressId');
	}

	/**
	 * @param string $lastCartIdentifier
	 * @return $this
	 */
	public function setLastCartIdentifier($lastCartIdentifier)
	{
		$this->properties['lastCartIdentifier'] = $lastCartIdentifier;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastCartIdentifier()
	{
		return isset($this->properties['lastCartIdentifier']) ? $this->properties['lastCartIdentifier'] : null;
	}

	/**
	 * @param string $defaultZone
	 * @return $this
	 */
	public function setDefaultZone($defaultZone)
	{
		$this->properties['defaultZone'] = $defaultZone;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultZone()
	{
		return isset($this->properties['defaultZone'])? $this->properties['defaultZone'] : null;
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
	 * @param \Rbs\Price\Documents\BillingArea $defaultBillingArea
	 * @return $this
	 */
	public function setDefaultBillingArea($defaultBillingArea)
	{
		if ($defaultBillingArea instanceof \Rbs\Price\Documents\BillingArea)
		{
			$this->properties['defaultBillingAreaId'] = $defaultBillingArea->getId();
		}
		else
		{
			$this->properties['defaultBillingAreaId'] = null;
		}
		return $this;
	}

	/**
	 * @param integer $defaultBillingAreaId
	 * @return $this
	 */
	public function setDefaultBillingAreaId($defaultBillingAreaId)
	{
		$this->properties['defaultBillingAreaId'] = $defaultBillingAreaId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getDefaultBillingAreaId()
	{
		return isset($this->properties['defaultBillingAreaId']) ? $this->properties['defaultBillingAreaId'] : null;
	}

	/**
	 * @param integer $defaultAddressId
	 * @return $this
	 */
	public function setDefaultAddressId($defaultAddressId)
	{
		$this->properties['defaultAddressId'] = $defaultAddressId;
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getDefaultAddressId()
	{
		return isset($this->properties['defaultAddressId']) ? $this->properties['defaultAddressId'] : null;
	}
}