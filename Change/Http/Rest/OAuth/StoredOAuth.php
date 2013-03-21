<?php
namespace Change\Http\Rest\OAuth;

/**
 * Class StoredOAuth
 * @package Change\Http\Rest\OAuth
 * @name \Change\Http\Rest\OAuth\StoredOAuth
 */
class StoredOAuth
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
	 * @var string
	 */
	protected $consumerKey;

	/**
	 * @var string
	 */
	protected $consumerSecret;

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
	 * @param string $consumerKey
	 */
	public function setConsumerKey($consumerKey)
	{
		$this->consumerKey = $consumerKey;
	}

	/**
	 * @return string
	 */
	public function getConsumerKey()
	{
		return $this->consumerKey;
	}

	/**
	 * @param string $consumerSecret
	 */
	public function setConsumerSecret($consumerSecret)
	{
		$this->consumerSecret = $consumerSecret;
	}

	/**
	 * @return string
	 */
	public function getConsumerSecret()
	{
		return $this->consumerSecret;
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
	 * @param array $array
	 */
	public function importFromArray(array $array)
	{
		foreach ($array as $k => $v)
		{
			switch ($k)
			{
				case 'id':
					$this->id = $v;
					break;
				case 'token':
				case 'oauth_token':
					$this->token = $v;
					break;
				case 'consumer_key':
				case 'oauth_consumer_key':
					$this->consumerKey = $v;
					break;
				case 'callback':
				case 'oauth_callback':
					$this->callback = $v;
					break;
				case 'consumer_secret':
					$this->consumerSecret = $v;
					break;
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
			}
		}
	}
}