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
		$cfg = $this->documentServices->getApplicationServices()->getApplication()->getConfiguration();
		$salt = $cfg->getEntry('Change/Users/salt');
		return md5($salt . '-' . $password);
	}

	/**
	 * @param string $password
	 * @return boolean
	 */
	public function checkPassword($password)
	{
		return $this->getPasswordmd5() === $this->encodePassword($password);
	}

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
		$this->setPasswordmd5($this->encodePassword($password));
	}
}