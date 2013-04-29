<?php
namespace Change\Http;

/**
 * @name \Change\Http\AclInterface
 */
class ConfigurationAcl implements  AclInterface
{
	/**
	 * @var AuthenticationInterface
	 */
	protected $authentication;

	/**
	 * @var boolean
	 */
	protected $allowAnonymous = true;

	/**
	 * @param AuthenticationInterface $authentication
	 */
	public function __construct(AuthenticationInterface $authentication = null)
	{
		$this->authentication = $authentication;
	}

	/**
	 * @param AuthenticationInterface $authentication
	 */
	public function setAuthentication(AuthenticationInterface $authentication)
	{
		$this->authentication = $authentication;
	}

	/**
	 * @return AuthenticationInterface
	 */
	public function getAuthentication()
	{
		return $this->authentication;
	}

	/**
	 * @param boolean $allowAnonymous
	 */
	public function setAllowAnonymous($allowAnonymous)
	{
		$this->allowAnonymous = ($allowAnonymous == true);
	}

	/**
	 * @return boolean
	 */
	public function getAllowAnonymous()
	{
		return $this->allowAnonymous;
	}

	/**
	 * @param mixed $resource
	 * @param string $privilege
	 * @return boolean
	 */
	public function hasPrivilege($resource, $privilege)
	{
		return ($this->allowAnonymous || $this->authentication->isAuthenticated());
	}
}