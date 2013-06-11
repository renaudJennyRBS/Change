<?php
namespace Change\Http\Rest\OAuth;

use Change\Db\DbProvider;
use Change\Db\Query\ResultsConverter;
use Change\Db\ScalarType;
use Change\Documents\DocumentManager;
use Change\Documents\Interfaces\Publishable;
use Change\Http\Event as HttpEvent;
use Change\Http\Rest\Result\ArrayResult;
use Change\Http\Rest\Result\ErrorResult;

use Zend\Http\Header\Accept;
use Zend\Http\Header\Authorization;
use ZendOAuth\Http\Utility;
use Zend\Http\Response as HttpResponse;
use Zend\Uri\Http as HttpUri;

/**
 * @name \Change\Http\Rest\OAuth\AuthenticationListener
 */
class AuthenticationListener
{
	const requestRegisterPath = '/OAuth/Register';

	const requestTokenPath = '/OAuth/RequestToken';

	const authorizePath = '/OAuth/Authorize';

	const accessTokenPath = '/OAuth/AccessToken';

	/**
	 * @var array
	 */
	protected $config;

	public function setConfig(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Default TODO_SET_CONSUMER_KEY set in Change/Http/Rest/OAuth/consumerKey
	 * @return string
	 */
	protected function getDefaultConsumerKey()
	{
		return isset($this->config['consumerKey']) ? $this->config['consumerKey'] : 'TODO_SET_CONSUMER_KEY';
	}

	/**
	 * Default TODO_SET_CONSUMER_SECRET set in Change/Http/Rest/OAuth/consumerSecret
	 * @return string
	 */
	protected function getDefaultConsumerSecret()
	{
		return isset($this->config['consumerSecret']) ? $this->config['consumerSecret'] : 'TODO_SET_CONSUMER_SECRET';
	}

	/**
	 * Default 60 seconds set in Change/Http/Rest/OAuth/timestampMaxOffset
	 * @return integer
	 */
	protected function getTimestampMaxOffset()
	{
		return isset($this->config['timestampMaxOffset']) ? intval($this->config['timestampMaxOffset']) : 60;
	}

	/**
	 * Default P10Y 10 years set in Change/Http/Rest/OAuth/tokenAccessValidity
	 * @return string
	 */
	protected function getTokenAccessValidity()
	{
		return isset($this->config['tokenAccessValidity']) ? $this->config['tokenAccessValidity'] : 'P10Y';
	}

	/**
	 * Default P1D 1 day set in Change/Http/Rest/OAuth/tokenRequestValidity
	 * @return string
	 */
	protected function getTokenRequestValidity()
	{
		return isset($this->config['tokenRequestValidity']) ? $this->config['tokenRequestValidity'] : 'P1D';
	}

	/**
	 * @param HttpEvent $event
	 */
	public function onRequest($event)
	{
		if (!$event instanceof HttpEvent)
		{
			return;
		}

		if (null === $this->config)
		{
			$cfg = $event->getApplicationServices()->getApplication()->getConfiguration();
			$this->setConfig($cfg->getEntry('Change/Http/Rest/OAuth', array()));
		}

		$request = $event->getRequest();
		$path = $request->getPath();
		if (strpos($path, static::requestRegisterPath) === 0)
		{
			if ('POST' === $request->getMethod())
			{
				$this->onRegister($event);
			}
			else
			{
				$event->setResult($this->buildNotAllowedError($request->getMethod(), array('POST')));
			}
		}
		elseif (strpos($path, static::requestTokenPath) === 0)
		{
			$this->onRequestToken($event);
		}
		elseif (strpos($path, static::authorizePath) === 0)
		{
			if ('POST' === $request->getMethod())
			{
				$this->onAuthorize($event);
			}

			if ($event->getResult() === null)
			{
				$array = array('oauth_token' => $request->getPost('oauth_token', $request->getQuery('oauth_token')));
				$result  = new ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$result->setArray($array);
				$event->setResult($result);
			}
		}
		elseif (strpos($path, static::accessTokenPath) === 0)
		{
			$this->onAccessToken($event);
		}
		else
		{
			$this->onAuthenticate($event);
		}
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	protected function onRegister(HttpEvent $event)
	{
		$request = $event->getRequest();
		$realm = $request->getPost('realm');
		$login = $request->getPost('login');
		$password = $request->getPost('password');
		if ($realm && $login && $password)
		{
			$accessorId = $this->findAccessorId($realm, $login, $password, $event->getDocumentServices()->getDocumentManager());
			if ($accessorId)
			{
				$array = array('accessor_id' => $accessorId);
				$array['consumer_key'] = $this->getDefaultConsumerKey();
				$array['consumer_secret'] = $this->getDefaultConsumerSecret();
				$result  = new ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$result->setArray($array);
				$event->setResult($result);
				return;
			}
			$result = new ErrorResult('AUTHENTICATION-ERROR', 'Unable to authenticate', HttpResponse::STATUS_CODE_403);
			$event->setResult($result);
		}
		else
		{
			throw new \RuntimeException('Invalid Parameter: realm, login, password', 71000);
		}
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	protected function onRequestToken(HttpEvent $event)
	{
		$request = $event->getRequest();
		$authorization = $this->parseAuthorizationHeader($request->getHeader('Authorization'));
		if (count($authorization) && isset($authorization['oauth_timestamp']) && isset($authorization['oauth_consumer_key']))
		{
			if ($authorization['oauth_consumer_key'] !== $this->getDefaultConsumerKey())
			{
				throw new \RuntimeException('Invalid OAuth Consumer Key: ' . $authorization['oauth_consumer_key'], 72001);
			}
			$this->checkTimestamp($authorization['oauth_timestamp']);

			$storeOAuth = new StoredOAuth();
			$storeOAuth->importFromArray($authorization);
			$storeOAuth->setConsumerSecret($this->getDefaultConsumerSecret());
			$storeOAuth->setType(StoredOAuth::TYPE_REQUEST);
			list($method, $url, $params) = $this->buildSignParams($authorization, $request);

			$utils = new Utility();
			$signature = $utils->sign($params , $authorization['oauth_signature_method'], $storeOAuth->getConsumerSecret(), $storeOAuth->getTokenSecret(), $method, $url);
			if ($signature === $authorization['oauth_signature'])
			{
				$storeOAuth->setToken($this->generateKey(true));
				$storeOAuth->setTokenSecret($this->generateKey());
				if (!$storeOAuth->getRealm())
				{
					$storeOAuth->setRealm('rest');
				}

				$this->insertToken($storeOAuth, $event->getApplicationServices()->getDbProvider());

				$array = array('oauth_token' => $storeOAuth->getToken(), 'oauth_token_secret' => $storeOAuth->getTokenSecret(), 'oauth_callback_confirmed'=> true);
				$result  = new ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$result->setArray($array);
				$event->setResult($result);
			}
			else
			{
				throw new \RuntimeException('Invalid Signature', 72000);
			}
		}
		else
		{
			throw new \RuntimeException('Invalid OAuth Authorization', 72002);
		}
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	protected function onAuthorize(HttpEvent $event)
	{
		$request = $event->getRequest();
		$token = $request->getPost('oauth_token', $request->getQuery('oauth_token'));
		if (!$token)
		{
			throw new \RuntimeException('Invalid Parameter: oauth_token', 71000);
		}

		$login = $request->getPost('login');
		$password = $request->getPost('password');
		$realm = $request->getPost('realm');
		if ($realm && $login && $password)
		{
			$storeOAuth = new StoredOAuth();
			$storeOAuth->setToken($token);

			$this->loadToken($storeOAuth, $event->getApplicationServices()->getDbProvider());
			if ($storeOAuth->getId() && $storeOAuth->getType() === StoredOAuth::TYPE_REQUEST && !$storeOAuth->getAuthorized())
			{
				$accessorId = $this->findAccessorId($realm, $login, $password, $event->getDocumentServices()->getDocumentManager());
				if ($accessorId)
				{
					$storeOAuth->setAccessorId($accessorId);
					$storeOAuth->setAuthorized(true);
					$storeOAuth->setVerifier(substr(md5(uniqid()),0, 10));
					$validityDate = new \DateTime();
					$storeOAuth->setValidityDate($validityDate->add(new \DateInterval($this->getTokenRequestValidity())));
					$this->updateToken($storeOAuth, $event->getApplicationServices()->getDbProvider());

					$array = array('oauth_callback' => $storeOAuth->getCallback(), 'oauth_token' => $storeOAuth->getToken(),
						'oauth_verifier'=> $storeOAuth->getVerifier());

					$result  = new ArrayResult();
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
					$result->setArray($array);
					$event->setResult($result);
				}
			}
		}
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	protected function onAccessToken(HttpEvent $event)
	{
		$request = $event->getRequest();
		$authorization = $this->parseAuthorizationHeader($request->getHeader('Authorization'));
		if (count($authorization) && isset($authorization['oauth_token']) && isset($authorization['oauth_timestamp']) && isset($authorization['oauth_verifier']))
		{
			$this->checkTimestamp($authorization['oauth_timestamp']);
			$storeOAuth = new StoredOAuth();
			$storeOAuth->importFromArray($authorization);
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$this->loadToken($storeOAuth, $dbProvider);

			$now = new \DateTime();
			if ($storeOAuth->getId() && StoredOAuth::TYPE_REQUEST === $storeOAuth->getType()
				&& $storeOAuth->getAuthorized() && $storeOAuth->getValidityDate() > $now &&
				$storeOAuth->getVerifier() === $authorization['oauth_verifier'])
			{
				list($method, $url, $params) = $this->buildSignParams($authorization, $request);
				$utils = new Utility();
				$signature = $utils->sign($params , $authorization['oauth_signature_method'], $storeOAuth->getConsumerSecret(), $storeOAuth->getTokenSecret(), $method, $url);

				if ($signature === $authorization['oauth_signature'])
				{
					$finalStoreOAuth = clone($storeOAuth);

					$finalStoreOAuth->setType(StoredOAuth::TYPE_ACCESS);
					$finalStoreOAuth->setId(null);
					$finalStoreOAuth->setVerifier(null);
					$finalStoreOAuth->setCallback('oob');

					$validityDate = new \DateTime();
					$finalStoreOAuth->setValidityDate($validityDate->add(new \DateInterval($this->getTokenAccessValidity())));
					$finalStoreOAuth->setToken($this->generateKey(true));
					$finalStoreOAuth->setTokenSecret($this->generateKey());
					$this->insertToken($finalStoreOAuth, $dbProvider);
					if ($finalStoreOAuth->getId())
					{
						$storeOAuth->setVerifier(null);
						$storeOAuth->setValidityDate($now);
						$this->updateToken($storeOAuth, $dbProvider);

						$array = array('oauth_token' => $finalStoreOAuth->getToken(), 'oauth_token_secret' => $finalStoreOAuth->getTokenSecret());
						$result  = new ArrayResult();
						$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
						$result->setArray($array);
						$event->setResult($result);
					}
					else
					{
						throw new \RuntimeException('Unable to create Token', 72003);
					}
				}
				else
				{
					throw new \RuntimeException('Invalid Signature', 72000);
				}
			}
			else
			{
				throw new \RuntimeException('Invalid OAuth Token: ' . $storeOAuth->getToken(), 72004);
			}
		}
		else
		{
			throw new \RuntimeException('Invalid OAuth Authorization', 72002);
		}
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	protected function onAuthenticate(HttpEvent $event)
	{
		$authentication = new Authentication();
		$event->setAuthentication($authentication);

		$request = $event->getRequest();
		$authorization = $this->parseAuthorizationHeader($request->getHeader('Authorization'));
		if (count($authorization) && isset($authorization['oauth_token']) && isset($authorization['oauth_timestamp']))
		{
			$storeOAuth = new StoredOAuth();
			$storeOAuth->importFromArray($authorization);
			if (!$storeOAuth->getToken())
			{
				throw new \RuntimeException('Invalid OAuth Token: ' . $storeOAuth->getToken(), 72004);
			}

			$this->checkTimestamp($authorization['oauth_timestamp']);

			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$this->loadToken($storeOAuth, $dbProvider);
			$now = new \DateTime();

			if ($storeOAuth->getId() && StoredOAuth::TYPE_ACCESS === $storeOAuth->getType() && $storeOAuth->getValidityDate() > $now)
			{
				list($method, $url, $params) = $this->buildSignParams($authorization, $request);
				$utils = new Utility();
				$signature = $utils->sign($params , $authorization['oauth_signature_method'], $storeOAuth->getConsumerSecret(), $storeOAuth->getTokenSecret(), $method, $url);
				if ($signature === $authorization['oauth_signature'])
				{
					$authentication->setStoredOAuth($storeOAuth);
				}
				else
				{
					throw new \RuntimeException('Invalid Signature', 72000);
				}
			}
			else
			{
				throw new \RuntimeException('Invalid OAuth Token: ' . $storeOAuth->getToken(), 72004);
			}
		}
	}


	/**
	 * @param string $timestamp
	 * @throws \RuntimeException
	 */
	protected function checkTimestamp($timestamp)
	{
		$delay = abs(time() - $timestamp);
		if ($delay > $this->getTimestampMaxOffset())
		{
			throw new \RuntimeException('Invalid Timestamp: ' . $delay, 72005);
		}
	}

	/**
	 * @param array $authorization
	 * @param \Change\Http\Request $request
	 * @return array
	 */
	protected function buildSignParams($authorization, $request)
	{
		unset($authorization['realm']);
		$utils = new Utility();
		$uri = new HttpUri($request->getUri());

		$query = $utils->parseQueryString($uri->getQuery());
		$uri->setQuery(null);
		$method = $request->getMethod();
		$url = $uri->toString();

		$ct = $request->getHeader('Content-Type');
		if ($ct && $ct->getFieldValue() == 'application/x-www-form-urlencoded')
		{
			$post = $request->getPost()->toArray();
		}
		else
		{
			$post = array();
		}
		return array($method, $url, array_merge($authorization, $query, $post));
	}

	/**
	 * @param \Zend\Http\Header\HeaderInterface $authorization
	 * @return array
	 */
	protected function parseAuthorizationHeader($authorization)
	{
		if ($authorization instanceof Authorization)
		{
			$rawHeader = $authorization->getFieldValue();
			if (strpos($rawHeader, 'OAuth') === 0)
			{
				$headers = array();
				foreach (explode(',', trim(substr($rawHeader, 5))) as $rawPart)
				{
					$part = trim($rawPart);
					$firstEqual = strpos($part, '=');
					$name = rawurldecode(substr($part, 0, $firstEqual));
					if (strpos($name, 'oauth_') === 0 || $name === 'realm')
					{
						$value = substr($part, $firstEqual+1);
						if (strlen($value) > 1 && $value[0] == '"' && $value[strlen($value)-1] == '"')
						{
							$value = substr($value, 1, strlen($value)-2);
						}
						$headers[$name] = rawurldecode($value);
					}
				}
				return $headers;
			}
		}
		return array();
	}

	/**
	 * Generate a unique key
	 * @param boolean $unique force the key to be unique
	 * @return string
	 */
	protected function generateKey($unique = false)
	{
		$key = md5(uniqid(rand(), true));
		if ($unique)
		{
			list($uSec, $sec) = explode(' ', microtime());
			$key .= dechex($uSec) . dechex($sec);
		}
		return $key;
	}

	/**
	 * @param StoredOAuth $storedOAuth
	 * @param DbProvider $dbProvider
	 */
	protected function loadToken($storedOAuth, DbProvider $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();

		$sq = $qb->select($fb->alias($fb->column('token_id'), 'id'), 'token', 'token_secret',
			'consumer_key', 'consumer_secret', 'realm',
			'token_type', 'creation_date', 'validity_date',
			'callback', 'verifier', 'authorized', 'accessor_id')
			->from('change_oauth')
			->where($fb->eq($fb->column('token'), $fb->parameter('token')))
			->query();
		$sq->bindParameter('token', $storedOAuth->getToken());

		$rc = new ResultsConverter($dbProvider, array('id' => ScalarType::INTEGER,
			'creation_date' => ScalarType::DATETIME,
			'validity_date' => ScalarType::DATETIME,
			'authorized' => ScalarType::BOOLEAN,
			'accessor_id' => ScalarType::INTEGER));

		$array = $sq->getFirstResult(array($rc, 'convertRow'));
		if (is_array($array))
		{
			$storedOAuth->importFromArray($array);
		}
	}

	/**
	 * @param StoredOAuth $storedOAuth
	 * @param DbProvider $dbProvider
	 */
	protected function insertToken($storedOAuth, DbProvider $dbProvider)
	{
		if (null === $storedOAuth->getCreationDate())
		{
			$storedOAuth->setCreationDate(new \DateTime());
		}

		if (null === $storedOAuth->getAuthorized())
		{
			$storedOAuth->setAuthorized(false);
		}

		if (null === $storedOAuth->getCallback() && StoredOAuth::TYPE_REQUEST === $storedOAuth->getType())
		{
			$storedOAuth->setCallback('oob');
		}

		$qb = $dbProvider->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();
		$iq = $qb->insert('change_oauth')->addColumns('token', 'token_secret',
			'consumer_key', 'consumer_secret', 'realm',
			'token_type', 'creation_date', 'validity_date',
			'callback', 'verifier', 'authorized', 'accessor_id')
			->addValues($fb->parameter('token'), $fb->parameter('token_secret'),
				$fb->parameter('consumer_key'), $fb->parameter('consumer_secret'), $fb->parameter('realm'),
				$fb->parameter('token_type'), $fb->dateTimeParameter('creation_date'), $fb->dateTimeParameter('validity_date'),
				$fb->parameter('callback'), $fb->parameter('verifier'), $fb->booleanParameter('authorized'), $fb->integerParameter('accessor_id'))
			->insertQuery();

		$iq->bindParameter('token', $storedOAuth->getToken());
		$iq->bindParameter('token_secret', $storedOAuth->getTokenSecret());
		$iq->bindParameter('consumer_key', $storedOAuth->getConsumerKey());
		$iq->bindParameter('consumer_secret', $storedOAuth->getConsumerSecret());
		$iq->bindParameter('realm', $storedOAuth->getRealm());
		$iq->bindParameter('token_type', $storedOAuth->getType());
		$iq->bindParameter('creation_date', $storedOAuth->getCreationDate());
		$iq->bindParameter('validity_date', $storedOAuth->getValidityDate());
		$iq->bindParameter('callback', $storedOAuth->getCallback());
		$iq->bindParameter('verifier', $storedOAuth->getVerifier());
		$iq->bindParameter('authorized', $storedOAuth->getAuthorized());
		$iq->bindParameter('accessor_id', $storedOAuth->getAccessorId());
		$iq->execute();

		$storedOAuth->setId(intval($dbProvider->getLastInsertId('change_oauth')));
	}

	/**
	 * @param StoredOAuth $storedOAuth
	 * @param DbProvider $dbProvider
	 */
	protected function updateToken($storedOAuth, DbProvider $dbProvider)
	{
		$qb = $dbProvider->getNewStatementBuilder();
		$fb = $qb->getFragmentBuilder();

		$uq = $qb->update('change_oauth')
			->assign('validity_date', $fb->dateTimeParameter('validity_date'))
			->assign('verifier', $fb->parameter('verifier'))
			->assign('authorized', $fb->booleanParameter('authorized'))
			->assign('accessor_id', $fb->integerParameter('accessor_id'))
			->where($fb->eq($fb->column('token_id'), $fb->integerParameter('id')))
			->updateQuery();

		$uq->bindParameter('validity_date', $storedOAuth->getValidityDate());
		$uq->bindParameter('verifier', $storedOAuth->getVerifier());
		$uq->bindParameter('authorized', $storedOAuth->getAuthorized());
		$uq->bindParameter('accessor_id', $storedOAuth->getAccessorId());
		$uq->bindParameter('id', $storedOAuth->getId());
		$uq->execute();
	}

	/**
	 * @param string $realm
	 * @param string $login
	 * @param string $password
	 * @param DocumentManager $documentManager
	 * @return integer|null
	 */
	protected function findAccessorId($realm, $login, $password, DocumentManager $documentManager)
	{
		$dbProvider = $documentManager->getApplicationServices()->getDbProvider();
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$gtb = $fb->getDocumentTable('Rbs_Users_Group');
		$utb = $fb->getDocumentTable('Rbs_Users_User');
		$rtb = $fb->getDocumentRelationTable('Rbs_Users_User');

		$sq = $qb->select()
			->addColumn($fb->alias($fb->getDocumentColumn('id', $utb), 'id'))
			->addColumn($fb->alias($fb->getDocumentColumn('model', $utb), 'model'))
			->from($utb)
			->innerJoin($rtb, $fb->eq($fb->getDocumentColumn('id', $utb), $fb->getDocumentColumn('id', $rtb)))
			->innerJoin($gtb, $fb->eq($fb->getDocumentColumn('id', $gtb), $fb->column('relatedid', $rtb)))
			->where(
				$fb->logicAnd(
					$fb->eq($fb->column('realm', $gtb), $fb->parameter('realm')),
					$fb->eq($fb->getDocumentColumn('login', $utb), $fb->parameter('login')),
					$fb->eq($fb->getDocumentColumn('publicationStatus', $utb), $fb->string(Publishable::STATUS_PUBLISHABLE))
					)
				)
			->query();
		$sq->bindParameter('realm', $realm);
		$sq->bindParameter('login', $login);

		$collection = new \Change\Documents\DocumentCollection($documentManager, $sq->getResults());
		foreach ($collection as $document)
		{
			if ($document instanceof \Rbs\Users\Documents\User)
			{
				if ($document->published() && $document->checkPassword($password))
				{
					return $document->getId();
				}
			}
		}
		return null;
	}

	/**
	 * @param HttpEvent $event
	 */
	public function onResponse($event)
	{
		if (!$event instanceof HttpEvent)
		{
			return;
		}

		$request = $event->getRequest();
		$header = $request->getHeader('Accept');
		if ($header instanceof Accept)
		{
			foreach ($header->getPrioritized() as $part)
			{
				/* @var $part \Zend\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart */
				if (strpos($part->getTypeString(), 'application/json')  === 0)
				{
					return;
				}
			}
		}

		$path = $request->getPath();
		if (strpos($path, static::requestTokenPath) === 0 || strpos($path, static::accessTokenPath) === 0)
		{
			$result = $event->getResult();
			if ($result instanceof ArrayResult)
			{
				$response = new \Zend\Http\PhpEnvironment\Response();
				$response->setStatusCode($result->getHttpStatusCode());
				$response->setHeaders($result->getHeaders());
				$response->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded');
				$array = $result->toArray();

				if (isset($array['oauth_callback_confirmed']))
				{
					$array['oauth_callback_confirmed'] = 'true';
				}

				$response->setContent(http_build_query($array));
				$event->setResponse($response);
				$event->stopPropagation();
			}
		}
		elseif (strpos($path, static::authorizePath) === 0)
		{
			$result = $event->getResult();
			if ($result instanceof ArrayResult)
			{
				$array = $result->toArray();
				$response = new \Zend\Http\PhpEnvironment\Response();
				$response->setHeaders($result->getHeaders());
				$response->setStatusCode($result->getHttpStatusCode());
				if (isset($array['oauth_callback']))
				{
					if ($array['oauth_callback'] !== 'oob')
					{
						$response->setStatusCode(HttpResponse::STATUS_CODE_301);
						$uri = new HttpUri($array['oauth_callback']);
						$query = $uri->getQueryAsArray();
						$query['oauth_token'] = $array['oauth_token'];
						$query['oauth_verifier'] = $array['oauth_verifier'];
						$uri->setQuery($query);

						$response->getHeaders()->addHeaderLine('Location', $uri->normalize()->toString());
						$event->setResponse($response);
						$event->stopPropagation();
					}
					else
					{
						$response->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded');
						$response->setContent(http_build_query($array));
						$event->setResponse($response);
						$event->stopPropagation();
					}
				}
				else
				{
					$response->getHeaders()->addHeaderLine('Content-Type', 'text/html');
					$html = file_get_contents(__DIR__ . '/Assets/login.html');
					$html = str_replace('{oauth_token}', $array['oauth_token'], $html);
					$response->setContent($html);
					$event->setResponse($response);
					$event->stopPropagation();
				}
			}
		}
	}


	/**
	 * @param string $notAllowed
	 * @param string[] $allow
	 * @return ErrorResult
	 */
	protected function buildNotAllowedError($notAllowed, array $allow)
	{
		$msg = 'Method not allowed: ' . $notAllowed;
		$result = new ErrorResult('METHOD-ERROR', $msg, Response::STATUS_CODE_405);
		$header = \Zend\Http\Header\Allow::fromString('allow: ' . implode(', ', $allow));
		$result->getHeaders()->addHeader($header);
		$result->addDataValue('allow', $allow);
		return $result;
	}
}