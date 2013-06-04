<?php
namespace Rbs\Users\Documents;

use Change\Stdlib\String;

/**
 * Class User
 * @package Rbs\Users\Documents
 * @name \Rbs\Users\Documents\User
 */
class User extends \Compilation\Rbs\Users\Documents\User
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
		$cfg = $this->getDocumentServices()->getApplicationServices()->getApplication()->getConfiguration();
		return $cfg->getEntry('Rbs/Users/salt');
	}

	/**
	 * @param string $password
	 * @return string
	 */
	protected function hashPassword($password)
	{
		$hashMethod = $this->getHashMethod();
		$callable = array($this, 'hashPassword' . ucfirst(strtolower($hashMethod)));
		if (is_callable($callable))
		{
			return call_user_func($callable, $password);
		}
		return $this->hashPasswordUsingHashMethod($password, $hashMethod);
	}

	/**
	 * @param string $password
	 * @return string
	 */
	protected function hashPasswordBcrypt($password)
	{
		$options = array();
		$cfg = $this->getDocumentServices()->getApplicationServices()->getApplication()->getConfiguration();
		$logging = $this->getDocumentServices()->getApplicationServices()->getLogging();
		$cost = $cfg->getEntry('Rbs/Users/bcrypt/cost');
		if ($cost)
		{
			$options['cost'] = $cost;
		}
		$saltString = $this->getSaltString();
		if (!String::isEmpty($saltString))
		{
			if (String::length($saltString) > 21)
			{
				$options['salt'] = $saltString;
			}
			else
			{
				$logging->info('salt is too short for bcrypt - using auto-generated salt instead');
			}
		}
		return password_hash($password, PASSWORD_BCRYPT, $options);
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return string
	 */
    protected function hashPasswordUsingHashMethod($password, $hashMethod)
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
		$hashMethod = $this->getHashMethod();
		$callable = array($this, 'checkPassword' . ucfirst(strtolower($hashMethod)));
		if (is_callable($callable))
		{
			return call_user_func($callable, $password, $this->getPasswordHash());
		}
		return $this->getPasswordHash() === $this->hashPassword($password);
	}

	/**
	 * @param string $password
	 * @return string
	 */
	protected function checkPasswordBcrypt($password, $hash)
	{
		return password_verify($password, $hash);
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
			$this->setHashMethod('bcrypt');
		}

		if ($this->password)
		{
			$this->setPasswordHash($this->hashPassword($this->password));
		}
	}

	protected function onUpdate()
	{
		if ($this->password)
		{
			$this->setPasswordHash($this->hashPassword($this->password));
		}
	}
}