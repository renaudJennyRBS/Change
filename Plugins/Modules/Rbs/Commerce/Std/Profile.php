<?php
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
		return array('lastCartIdentifier', 'defaultZone', 'defaultBillingAreaId', 'defaultAddressId');
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
		return $this->properties['lastCartIdentifier'];
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
		return $this->properties['defaultZone'];
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
		return $this->properties['defaultBillingAreaId'];
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
		return $this->properties['defaultAddressId'];
	}
}