<?php
namespace Change\Users\Documents;

/**
 * Class User
 * @package Change\Users\Documents
 * @name \Change\Users\Documents\User
 */
class User extends \Compilation\Change\Users\Documents\User
{
	/**
	 * @param string $password
	 * @return string
	 */
	protected function encodePassword($password)
	{
		return md5($this->getEmail() . '-' . $password);
	}

	/**
	 * @param string $password
	 * @return boolean
	 */
	public function checkPassword($password)
	{
		return $this->getPasswordmd5() === $this->encodePassword($password);
	}
}