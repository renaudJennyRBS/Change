<?php
namespace Change\Http\Rest\OAuth;

use Change\Http\AuthenticationInterface;

/**
 * Class Authentication
 * @package Change\Http\Rest\OAuth
 * @name \Change\Http\Rest\OAuth\Authentication
 */
class Authentication implements AuthenticationInterface
{
	/**
	 * @var StoredOAuth
	 */
	protected $storedOAuth;

	/**
	 * @var Acl
	 */
	protected $acl;

	/**
	 * @param \Change\Http\Rest\OAuth\StoredOAuth $storedOAuth
	 */
	public function setStoredOAuth(StoredOAuth $storedOAuth = null)
	{
		$this->storedOAuth = $storedOAuth;
	}

	/**
	 * @return \Change\Http\Rest\OAuth\StoredOAuth
	 */
	public function getStoredOAuth()
	{
		return $this->storedOAuth;
	}

	/**
	 * @return boolean
	 */
	public function isAuthenticated()
	{
		return (null !== $this->storedOAuth) && ($this->storedOAuth->getAccessorId());
	}

	/**
	 * @return mixed|null
	 */
	public function getIdentity()
	{
		if ($this->isAuthenticated())
		{
			return $this->storedOAuth->getAccessorId();
		}
		return null;
	}

	/**
	 * @param \Change\Http\Rest\OAuth\Acl $acl
	 */
	public function setAcl($acl)
	{
		$this->acl = $acl;
	}

	/**
	 * @return AclInterface
	 */
	public function getAcl()
	{
		if ($this->acl === null)
		{
			$this->acl = new Acl($this);
		}
		return $this->acl;
	}
}