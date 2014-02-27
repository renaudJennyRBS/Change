<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http;

/**
 * @name \Change\Http\Event
 */
class Event extends \Change\Events\Event
{
	const EVENT_REQUEST = 'http.request';
	const EVENT_ACTION = 'http.action';
	const EVENT_RESULT = 'http.result';
	const EVENT_RESPONSE = 'http.response';
	const EVENT_EXCEPTION = 'http.exception';
	const EVENT_AUTHENTICATE = 'http.authenticate';

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var UrlManager
	 */
	protected $urlManager;

	/**
	 * @var Callable|null
	 */
	protected $authorization;

	/**
	 * @var Callable|null
	 */
	protected $action;

	/**
	 * @var Result
	 */
	protected $result;

	/**
	 * @var \Zend\Http\PhpEnvironment\Response
	 */
	protected $response;

	/**
	 * @api
	 * @return Controller|null
	 */
	public function getController()
	{
		if ($this->getTarget() instanceof Controller)
		{
			return $this->getTarget();
		}
		return null;
	}

	/**
	 * @param Request $request
	 */
	public function setRequest($request)
	{
		$this->request = $request;
	}

	/**
	 * @api
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param UrlManager $urlManager
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @api
	 * @return UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @api
	 * @return \Change\User\AuthenticationManager
	 */
	public function getAuthenticationManager()
	{
		return $this->getApplicationServices()->getAuthenticationManager();
	}

	/**
	 * @api
	 * @return \Change\Permissions\PermissionsManager
	 */
	public function getPermissionsManager()
	{
		return $this->getApplicationServices()->getPermissionsManager();
	}

	/**
	 * @api
	 * @param Callable|null $authorization
	 */
	public function setAuthorization($authorization)
	{
		$this->authorization = $authorization;
	}

	/**
	 * @api
	 * @return Callable|null
	 */
	public function getAuthorization()
	{
		return $this->authorization;
	}

	/**
	 * @api
	 * @param Callable|null $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @api
	 * @return Callable|null
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @api
	 * @param Result $result
	 */
	public function setResult($result)
	{
		$this->result = $result;
	}

	/**
	 * @api
	 * @return Result
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @api
	 * @param \Zend\Http\PhpEnvironment\Response|null $response
	 */
	public function setResponse($response)
	{
		$this->response = $response;
	}

	/**
	 * @api
	 * @return \Zend\Http\PhpEnvironment\Response|Null
	 */
	public function getResponse()
	{
		return $this->response;
	}
}