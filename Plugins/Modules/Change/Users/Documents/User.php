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
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getPseudonym())
		{
			return $this->getPseudonym();
		}
		return $this->getLogin();
	}

	/**
	 * @param string $label
	 */
	public function setLabel($label)
	{
		$this->setPseudonym($label);
	}

	/**
	 * return string|null
	 */
	protected function getSaltString()
	{
		$cfg = $this->documentServices->getApplicationServices()->getApplication()->getConfiguration();
		return $cfg->getEntry('Change/Users/salt');
	}

	/**
	 * @param string $password
	 * @return string
	 */
	protected function encodePassword($password)
	{
		$hashMethod = $this->getHashMethod();
		if (!$hashMethod)
		{
			$hashMethod = 'md5';
		}
		$callable = array($this, 'encode' . ucfirst(strtolower($hashMethod)) . 'Password');
		if (is_callable($callable))
		{
			return call_user_func($callable, $password);
		}
		return $this->encodePasswordUsingHash($password, $hashMethod);
	}

	/**
	 * @param string $password
	 * @return string
	 */
	protected function encodeSimpleMD5Password($password)
	{
		return md5($password);
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return string
	 */
    protected function encodePasswordUsingHash($password, $hashMethod)
	{
		if (in_array($hashMethod, hash_algos()))
		{
			$salt = $this->getSaltString();
			if ($salt)
			{
				return hash($hashMethod, $salt . '-' . $password);
			}
		}
		throw new \RuntimeException("hash $hashMethod does not exist");
	}

	/**
	 * @param string $password
	 * @return boolean
	 */
	public function checkPassword($password)
	{
		return $this->getPasswordHash() === $this->encodePassword($password);
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

	}

	protected function onCreate()
	{
		if (!$this->getHashMethod())
		{
			$this->setHashMethod('sha1');
		}

		if ($this->password)
		{
			$this->setPasswordHash($this->encodePassword($this->password));
		}
	}

	protected function onUpdate()
	{
		if ($this->password)
		{
			$this->setPasswordHash($this->encodePassword($this->password));
		}
	}
}