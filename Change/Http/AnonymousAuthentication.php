<?php
namespace Change\Http;

/**
 * @name \Change\Http\AuthenticationInterface
 */
class AnonymousAuthentication implements  AuthenticationInterface
{
	/**
	 * @var AclInterface
	 */
	protected $acl;

	/**
	 * @return boolean;
	 */
	public function isAuthenticated()
	{
		return false;
	}

	/**
	 * @return mixed|null
	 */
	public function getIdentity()
	{
		return null;
	}

	/**
	 * @return AclInterface
	 */
	public function getAcl()
	{
		return $this->acl;
	}

	/**
	 * @param \Change\Http\AclInterface $acl
	 */
	public function setAcl(AclInterface $acl)
	{
		$this->acl = $acl;
	}
}