<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\OAuth;

/**
 * @name \Change\Http\OAuth\OAuthDbEntry
 */
class OAuthDbEntry
{
	const TYPE_REQUEST = 'request';
	const TYPE_ACCESS = 'access';

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $token;

	/**
	 * @var string
	 */
	protected $tokenSecret;

	/**
	 * @var Consumer
	 */
	protected $consumer;

	/**
	 * @var string
	 */
	protected $realm;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var \DateTime
	 */
	protected $creationDate;

	/**
	 * @var \DateTime
	 */
	protected $validityDate;

	/**
	 * @var string
	 */
	protected $callback;

	/**
	 * @var string
	 */
	protected $verifier;

	/**
	 * @var boolean
	 */
	protected $authorized;

	/**
	 * @var integer
	 */
	protected $accessorId;

	/**
	 * @var string
	 */
	protected $device;

	/**
	 * @param int $accessorId
	 */
	public function setAccessorId($accessorId)
	{
		$this->accessorId = $accessorId;
	}

	/**
	 * @return int
	 */
	public function getAccessorId()
	{
		return $this->accessorId;
	}

	/**
	 * @param boolean $authorized
	 */
	public function setAuthorized($authorized)
	{
		$this->authorized = $authorized;
	}

	/**
	 * @return boolean
	 */
	public function getAuthorized()
	{
		return $this->authorized;
	}

	/**
	 * @param \Change\Http\OAuth\Consumer $consumer
	 */
	public function setConsumer($consumer)
	{
		$this->consumer = $consumer;
	}

	/**
	 * @return \Change\Http\OAuth\Consumer
	 */
	public function getConsumer()
	{
		return $this->consumer;
	}


	/**
	 * @param string $consumerKey
	 * @return $this
	 */
	public function setConsumerKey($consumerKey)
	{
		if ($this->consumer === null)
		{
			$this->consumer = new Consumer($consumerKey);
		}
		else
		{
			$this->consumer->setKey($consumerKey);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConsumerKey()
	{
		return $this->consumer ? $this->consumer->getKey() : null;
	}

	/**
	 * @param string $consumerSecret
	 * @return $this
	 */
	public function setConsumerSecret($consumerSecret)
	{
		if ($this->consumer === null)
		{
			$this->consumer = new Consumer(null, $consumerSecret);
		}
		else
		{
			$this->consumer->setSecret($consumerSecret);
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConsumerSecret()
	{
		return $this->consumer ? $this->consumer->getSecret() : null;
	}

	/**
	 * @param \DateTime $creationDate
	 */
	public function setCreationDate($creationDate)
	{
		$this->creationDate = $creationDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getCreationDate()
	{
		return $this->creationDate;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $realm
	 */
	public function setRealm($realm)
	{
		$this->realm = $realm;
	}

	/**
	 * @return string
	 */
	public function getRealm()
	{
		return $this->realm;
	}

	/**
	 * @param string $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @param string $tokenSecret
	 */
	public function setTokenSecret($tokenSecret)
	{
		$this->tokenSecret = $tokenSecret;
	}

	/**
	 * @return string
	 */
	public function getTokenSecret()
	{
		return $this->tokenSecret;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param \DateTime $validityDate
	 */
	public function setValidityDate($validityDate)
	{
		$this->validityDate = $validityDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getValidityDate()
	{
		return $this->validityDate;
	}

	/**
	 * @param string $callback
	 */
	public function setCallback($callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @return string
	 */
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	 * @param string $verifier
	 */
	public function setVerifier($verifier)
	{
		$this->verifier = $verifier;
	}

	/**
	 * @return string
	 */
	public function getVerifier()
	{
		return $this->verifier;
	}

	/**
	 * @param string $device
	 */
	public function setDevice($device)
	{
		$this->device = $device;
	}

	/**
	 * @return string
	 */
	public function getDevice()
	{
		return $this->device;
	}

	/**
	 * @param array $array
	 */
	public function importFromArray(array $array)
	{
		foreach ($array as $k => $v)
		{
			switch ($k)
			{
				case 'id':
				case 'token_id':
					$this->id = $v;
					break;
				case 'token':
				case 'oauth_token':
					$this->token = $v;
					break;
				case 'consumer_key':
				case 'oauth_consumer_key':
				{
					if ($this->getConsumer())
					{
						$this->consumer->setKey($v);
					}
					else
					{
						$this->consumer = new Consumer($v);
					}
					break;
				}
				case 'callback':
				case 'oauth_callback':
					$this->callback = $v;
					break;
				case 'consumer_secret':
				{
					if ($this->getConsumer())
					{
						$this->consumer->setSecret($v);
					}
					else
					{
						$this->consumer = new Consumer(null, $v);
					}
					break;
				}
				case 'application_id':
				{
					if (!$this->getConsumer())
					{
						$this->consumer = new Consumer(null, null);

					}
					$this->consumer->setApplicationId($v);
					break;
				}
				case 'application':
				{
					if (!$this->getConsumer())
					{
						$this->consumer = new Consumer(null, null);

					}
					$this->consumer->setApplicationName($v);
					break;
				}
				case 'token_secret':
					$this->tokenSecret = $v;
					break;
				case 'realm':
					$this->realm = $v;
					break;
				case 'token_type':
					$this->type = $v;
					break;
				case 'creation_date':
					$this->creationDate = $v;
					break;
				case 'validity_date':
					$this->validityDate = $v;
					break;
				case 'verifier':
					$this->verifier = $v;
					break;
				case 'authorized':
					$this->authorized = $v;
					break;
				case 'accessor_id':
					$this->accessorId = $v;
					break;
				case 'device':
					$this->device = $v;
					break;
			}
		}
	}
}