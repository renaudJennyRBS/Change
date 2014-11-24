<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Documents;

use Change\Http\Rest\V1\Link;
use Change\Stdlib\String;

/**
 * @name \Rbs\User\Documents\User
 */
class User extends \Compilation\Rbs\User\Documents\User
{
	/**
	 * @var \Change\Configuration\Configuration
	 */
	private $configuration;

	/**
	 * @return \Change\Configuration\Configuration
	 */
	protected function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onDefaultInjection(\Change\Events\Event $event)
	{
		parent::onDefaultInjection($event);
		$this->configuration = $event->getApplication()->getConfiguration();
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		$login = $this->getLogin();
		if (!\Change\Stdlib\String::isEmpty($login))
		{
			return $login;
		}
		return $this->getEmail();
	}

	/**
	 * @param string $label
	 * @return $this
	 */
	public function setLabel($label)
	{
		return $this;
	}

	/**
	 * return string|null
	 */
	protected function getSaltString()
	{
		$cfg = $this->getConfiguration();
		return $cfg->getEntry('Rbs/User/salt');
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
		$cfg = $this->getConfiguration();
		$cost = $cfg->getEntry('Rbs/User/bcrypt/cost');
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
		}
		return password_hash($password, PASSWORD_BCRYPT, $options);
	}

	/**
	 * @param string $password
	 * @param string $hashMethod
	 * @throws \RuntimeException
	 * @return string
	 */
	protected function hashPasswordUsingHashMethod($password, $hashMethod)
	{
		if (in_array($hashMethod, hash_algos()))
		{
			$saltString = $this->getSaltString();
			if (!String::isEmpty($saltString))
			{
				return hash($hashMethod, $saltString . '-' . $password);
			}
			else
			{
				return hash($hashMethod, $password);
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
	 * @param string $hash
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
	 * @return $this
	 */
	public function setPassword($password)
	{
		if (!\Change\Stdlib\String::isEmpty($password))
		{
			$this->password = $password;
			$this->setPasswordHash($this->hashPassword($this->password));
		}
		return $this;
	}

	/**
	 * @param \Change\Documents\Events\Event $event
	 */
	public function onDefaultUpdateRestResult(\Change\Documents\Events\Event $event)
	{
		parent::onDefaultUpdateRestResult($event);
		$restResult = $event->getParam('restResult');
		if ($restResult instanceof \Change\Http\Rest\V1\Resources\DocumentResult)
		{
			$documentResult = $restResult;
			$um = $documentResult->getUrlManager();
			$documentResult->addLink(new Link($um, $documentResult->getBaseUrl() . '/Profiles/', 'profiles'));
		}
	}
}