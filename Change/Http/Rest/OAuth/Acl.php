<?php
namespace Change\Http\Rest\OAuth;

use Change\Http\AclInterface;

/**
 * Class Authentication
 * @package Change\Http\Rest\OAuth
 * @name \Change\Http\Rest\OAuth\Authentication
 */
class Acl implements AclInterface
{
	/**
	 * @var Authentication
	 */
	protected $authentication;

	/**
	 * @param Authentication $authentication
	 */
	function __construct($authentication = null)
	{
		$this->authentication = $authentication;
	}

	/**
	 * @param \Change\Http\Rest\OAuth\Authentication $authentication
	 */
	public function setAuthentication($authentication)
	{
		$this->authentication = $authentication;
	}

	/**
	 * @return \Change\Http\Rest\OAuth\Authentication
	 */
	public function getAuthentication()
	{
		return $this->authentication;
	}

	/**
	 * @param mixed $resource
	 * @param string $privilege
	 * @return boolean
	 */
	public function hasPrivilege($resource, $privilege)
	{
		return true;
	}
}