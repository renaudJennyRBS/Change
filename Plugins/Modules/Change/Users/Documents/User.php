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
			$hashMethod = 'MD5';
		}
		$callable = array($this, 'encode' . ucfirst($hashMethod) . 'Password');
		if (is_callable($callable))
		{
			return call_user_func($callable, $password);
		}
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
	 * @param string $password
	 * @return string
	 */
	protected function encodeMD5Password($password)
	{
		$salt = $this->getSaltString();
		if ($salt)
		{
			return md5($salt . '-' . $password);
		}
		return $this->encodeSimpleMD5Password($password);
	}

	/**
	 * @param string $password
	 * @return string
	 */
	protected function encodeSha1Password($password)
	{
		$salt = $this->getSaltString();
		if ($salt)
		{
			return sha1($salt . '-' . $password);
		}
		return sha1($password);
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