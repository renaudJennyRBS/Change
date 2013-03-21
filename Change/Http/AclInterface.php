<?php
namespace Change\Http;

/**
 * @name \Change\Http\AclInterface
 */
interface AclInterface
{
	/**
	 * @param mixed $resource
	 * @param string $privilege
	 * @return boolean
	 */
	public function hasPrivilege($resource, $privilege);
}
