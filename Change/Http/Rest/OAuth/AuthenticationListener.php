<?php
namespace Change\Http\Rest\OAuth;

use Change\Http\Event as HttpEvent;
use Change\Http\Rest\Result\ArrayResult;
use Change\Http\Rest\Result\ErrorResult;

use Zend\Http\Header\Accept;
use Zend\Http\Header\Authorization;
use ZendOAuth\Http\Utility;
use Zend\Http\Response as HttpResponse;
use Zend\Uri\Http as HttpUri;
use Change\Http\Request;

/**
 * @name \Change\Http\Rest\OAuth\AuthenticationListener
 */
class AuthenticationListener
{
	const RESOLVER_NAME = 'OAuth';

	const RESOLVE_REQUEST_TOKEN = 'RequestToken';

	const RESOLVE_AUTHORIZE = 'Authorize';

	const RESOLVE_ACCESS_TOKEN = 'AccessToken';

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array('RequestToken', 'Authorize', 'AccessToken');
	}

	/**
	 * Set Event params: namespace, resolver,
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$nbParts = count($resourceParts);
		if ($nbParts == 0)
		{
			if ($method != Request::METHOD_GET)
			{
				$event->setResult($this->buildNotAllowedError($method, array(Request::METHOD_GET)));
				return;
			}
			array_unshift($resourceParts, static::RESOLVER_NAME);
			$event->setParam('namespace', implode('.', $resourceParts));
			$event->setParam('resolver', $this);
			$action = function ($event)
			{
				$action = new \Change\Http\Rest\Actions\DiscoverNameSpace();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($nbParts == 1)
		{
			$action = $this;
			if ($method != Request::METHOD_GET && $method != Request::METHOD_POST)
			{
				$event->setResult($this->buildNotAllowedError($method, array(Request::METHOD_GET, Request::METHOD_POST)));
				return;
			}
			if ($resourceParts[0] === static::RESOLVE_REQUEST_TOKEN)
			{
				$event->setAction(function($event) use($action) {$action->onRequestToken($event);});
			}
			elseif ($resourceParts[0] === static::RESOLVE_AUTHORIZE)
			{
				$event->setAction(function($event) use($action) {$action->onAuthorize($event);});
			}
			elseif ($resourceParts[0] === static::RESOLVE_ACCESS_TOKEN)
			{
				$event->setAction(function($event) use($action) {$action->onAccessToken($event);});
			}
		}
	}

	/**
	 * @param HttpEvent $event
	 */
	public function onRequest(HttpEvent $event)
	{
		$resolver = $event->getController()->getActionResolver();
		if ($resolver instanceof \Change\Http\Rest\Resolver)
		{
			$resolver->addResolverClasses(static::RESOLVER_NAME, get_class($this));
		}
	}

	/**
	 * @param \Change\Http\Request $request
	 * @return array
	 */
	protected function extractAuthorization($request)
	{
		$authorization = $this->parseAuthorizationHeader($request->getHeader('Authorization'));
		if (count($authorization))
		{
			return $authorization;
		}
		if ($request->getMethod() === \Zend\Http\Request::METHOD_POST)
		{
			$authorization = $request->getPost()->toArray();
			if (isset($authorization['oauth_signature']))
			{
				return $authorization;
			}
		}
		$authorization = $request->getQuery()->toArray();
		if (isset($authorization['oauth_signature']))
		{
			return $authorization;
		}
		return array();
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	public function onRequestToken(HttpEvent $event)
	{
		$request = $event->getRequest();
		$authorization = $this->extractAuthorization($request);

		if (count($authorization) && isset($authorization['oauth_timestamp']) && isset($authorization['oauth_consumer_key']))
		{
			$OAuth = new OAuth();
			$OAuth->setApplicationServices($event->getApplicationServices());
			$consumer = $OAuth->getConsumerByKey($authorization['oauth_consumer_key']);
			if (null === $consumer)
			{
				throw new \RuntimeException('Invalid OAuth Consumer Key: ' . $authorization['oauth_consumer_key'], 72001);
			}
			$OAuth->checkTimestamp($authorization['oauth_timestamp'], $consumer);

			$storedOAuth = new StoredOAuth();
			$storedOAuth->importFromArray($authorization);
			$storedOAuth->setConsumerSecret($consumer->getSecret());
			$storedOAuth->setType(StoredOAuth::TYPE_REQUEST);
			list($method, $url, $params) = $this->buildSignParams($authorization, $request);

			$utils = new Utility();
			$signature = $utils->sign($params , $authorization['oauth_signature_method'], $storedOAuth->getConsumerSecret(), $storedOAuth->getTokenSecret(), $method, $url);
			if ($signature === $authorization['oauth_signature'])
			{
				$storedOAuth->setToken($OAuth->generateTokenKey());
				$storedOAuth->setTokenSecret($OAuth->generateTokenSecret());
				if (!$storedOAuth->getRealm())
				{
					//Use default realm
					$storedOAuth->setRealm('rest');
				}

				$OAuth->insertToken($storedOAuth);

				$array = array('oauth_token' => $storedOAuth->getToken(), 'oauth_token_secret' => $storedOAuth->getTokenSecret(), 'oauth_callback_confirmed'=> true);
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
	public function onAuthorize(HttpEvent $event)
	{
		$request = $event->getRequest();
		$token = $request->getPost('oauth_token', $request->getQuery('oauth_token'));
		if (!$token)
		{
			throw new \RuntimeException('Invalid Parameter: oauth_token', 71000);
		}

		$OAuth = new OAuth();
		$OAuth->setApplicationServices($event->getApplicationServices());
		$storeOAuth = $OAuth->getRequestToken($token);
		if (null === $storeOAuth || $storeOAuth->getAuthorized())
		{
			throw new \RuntimeException('Invalid OAuth Token: ' . $token, 72004);
		}

		if ($request->getMethod() === Request::METHOD_POST)
		{
			$login = $request->getPost('login');
			$password = $request->getPost('password');
			$realm = $request->getPost('realm');
			if ($realm && $login && $password)
			{
				$am = $event->getAuthenticationManager();
				$user = $am->login($login, $password, $realm);
				if (null !== $user)
				{
					$storeOAuth->setAccessorId($user->getId());
					$storeOAuth->setAuthorized(true);
					$storeOAuth->setVerifier(substr(md5(uniqid()),0, 10));
					$validityDate = new \DateTime();
					$storeOAuth->setValidityDate($validityDate->add(new \DateInterval($storeOAuth->getConsumer()->getTokenRequestValidity())));
					$OAuth->updateToken($storeOAuth);

					$array = array('oauth_callback' => $storeOAuth->getCallback(), 'oauth_token' => $storeOAuth->getToken(),
						'oauth_verifier'=> $storeOAuth->getVerifier());

					$result  = new ArrayResult();
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
					$result->setArray($array);
					$event->setResult($result);
					return;
				}
				else
				{
					throw new \RuntimeException('Unable to authenticate', 999999);
				}
			}
		}

		$array = array('oauth_token' => $storeOAuth->getToken(), 'realm' => $storeOAuth->getRealm());
		$result  = new ArrayResult();
		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setArray($array);
		$event->setResult($result);
	}

	/**
	 * @param HttpEvent $event
	 * @throws \RuntimeException
	 */
	public function onAccessToken(HttpEvent $event)
	{
		$request = $event->getRequest();
		$authorization = $this->extractAuthorization($request);

		if (count($authorization) && isset($authorization['oauth_token']) && isset($authorization['oauth_timestamp']) && isset($authorization['oauth_verifier']))
		{
			$OAuth = new OAuth();
			$OAuth->setApplicationServices($event->getApplicationServices());
			$consumer = $OAuth->getConsumerByKey($authorization['oauth_consumer_key']);
			if (!$consumer)
			{
				throw new \RuntimeException('Invalid OAuth Consumer Key: ' . $authorization['oauth_consumer_key'], 72001);
			}
			$OAuth->checkTimestamp($authorization['oauth_timestamp'], $consumer);
			$storeOAuth = $OAuth->getStoredOAuth($authorization['oauth_token'], $authorization['oauth_consumer_key']);

			$now = new \DateTime();
			if (null !== $storeOAuth && StoredOAuth::TYPE_REQUEST === $storeOAuth->getType()
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
					$finalStoreOAuth->setValidityDate($validityDate->add(new \DateInterval($finalStoreOAuth->getConsumer()->getTokenAccessValidity())));
					$finalStoreOAuth->setToken($OAuth->generateTokenKey());
					$finalStoreOAuth->setTokenSecret($OAuth->generateTokenSecret());
					$OAuth->insertToken($finalStoreOAuth);
					if ($finalStoreOAuth->getId())
					{
						$storeOAuth->setVerifier(null);
						$storeOAuth->setValidityDate($now);
						$OAuth->updateToken($storeOAuth);

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
				throw new \RuntimeException('Invalid OAuth Token: ' . $authorization['oauth_token'], 72004);
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
	public function onAuthenticate(HttpEvent $event)
	{
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

			$oauth = new OAuth();
			$oauth->setApplicationServices($event->getApplicationServices());
			$consumer = $oauth->getConsumerByKey($authorization['oauth_consumer_key']);
			if(!$consumer)
			{
				throw new \RuntimeException('Invalid OAuth Consumer Key: ' . $authorization['oauth_consumer_key'], 72001);
			}
			$oauth->checkTimestamp($authorization['oauth_timestamp'], $consumer);
			$storeOAuth = $oauth->getStoredOAuth($authorization['oauth_token'], $authorization['oauth_consumer_key']);
			$now = new \DateTime();

			if (null !== $storeOAuth && StoredOAuth::TYPE_ACCESS === $storeOAuth->getType() && $storeOAuth->getValidityDate() > $now)
			{
				list($method, $url, $params) = $this->buildSignParams($authorization, $request);
				$utils = new Utility();
				$signature = $utils->sign($params , $authorization['oauth_signature_method'], $storeOAuth->getConsumerSecret(), $storeOAuth->getTokenSecret(), $method, $url);
				if ($signature === $authorization['oauth_signature'])
				{
					$user = $event->getAuthenticationManager()->getById($storeOAuth->getAccessorId());
					if ($user instanceof \Change\User\UserInterface)
					{
						$event->getAuthenticationManager()->setCurrentUser($user);
					}
					else
					{
						throw new \RuntimeException('Invalid OAuth AccessorId: ' . $storeOAuth->getAccessorId(), 72004);
					}
				}
				else
				{
					throw new \RuntimeException('Invalid Signature', 72000);
				}
			}
			else
			{
				throw new \RuntimeException('Invalid OAuth Token: ' . $authorization['oauth_token'], 72004);
			}
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
	 * @param HttpEvent $event
	 */
	public function onResponse(HttpEvent $event)
	{
		$pathPart = $event->getParam('pathParts');
		if (!is_array($pathPart) || count($pathPart) !== 2 || $pathPart[0] !== static::RESOLVER_NAME)
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


		if ($pathPart[1] === static::RESOLVE_REQUEST_TOKEN || $pathPart[1] === static::RESOLVE_ACCESS_TOKEN)
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
		elseif ($pathPart[1] === static::RESOLVE_AUTHORIZE)
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

					//TODO custom login form by realm/application
					$html = file_get_contents(__DIR__ . '/Assets/login.html');
					$html = str_replace('{oauth_token}', $array['oauth_token'], $html);
					$html = str_replace('{realm}', $array['realm'], $html);
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
		$result = new ErrorResult('METHOD-ERROR', $msg, \Zend\Http\Response::STATUS_CODE_405);
		$header = \Zend\Http\Header\Allow::fromString('allow: ' . implode(', ', $allow));
		$result->getHeaders()->addHeader($header);
		$result->addDataValue('allow', $allow);
		return $result;
	}
}