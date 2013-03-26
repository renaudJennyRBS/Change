<?php
namespace Change\Http;

/**
 * @name \Change\Http\AuthenticationInterface
 */
interface AuthenticationInterface
{
	/**
	 * @return boolean;
	 */
	public function isAuthenticated();

	/**
	 * @return mixed|null
	 */
	public function getIdentity();
}
