<?php
namespace Change\Http;

/**
 * @name \Change\Http\Event
 */
class Event extends \Zend\EventManager\Event
{
	const EVENT_REQUEST      = 'http.request';
	const EVENT_ACTION       = 'http.action';
	const EVENT_RESULT 		 = 'http.result';
	const EVENT_RESPONSE     = 'http.response';
	const EVENT_EXCEPTION    = 'http.exception';

	/**
	 * @var \Change\Application\ApplicationServices
	 */
	protected $applicationServices;

	/**
	 * @var \Change\Documents\DocumentServices
	 */
	protected $documentServices;

	/**
	 * @var \Change\Presentation\PresentationServices
	 */
	protected $presentationServices;

	/**
	 * @var \Change\Http\Request
	 */
	protected $request;

	/**
	 * @var \Change\Http\AuthenticationInterface|null
	 */
	protected $authentication;

	/**
	 * @var \Change\Http\AclInterface
	 */
	protected $acl;

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var callable|null
	 */
	protected $authorization;

	/**
	 * @var callable|null
	 */
	protected $action;

	/**
	 * @var \Change\Http\Result
	 */
	protected $result;

	/**
	 * @var \Zend\Http\PhpEnvironment\Response
	 */
	protected $response;

	/**
	 * @api
	 * @return \Change\Http\Controller|null
	 */
	public function getController()
	{
		if ($this->getTarget() instanceof \Change\Http\Controller)
		{
			return $this->getTarget();
		}
		return null;
	}

	/**
	 * @param \Change\Application\ApplicationServices|null $applicationServices
	 */
	public function setApplicationServices(\Change\Application\ApplicationServices $applicationServices = null)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @api
	 * @return \Change\Application\ApplicationServices|null
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices|null $documentServices
	 */
	public function setDocumentServices(\Change\Documents\DocumentServices $documentServices = null)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @api
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param \Change\Presentation\PresentationServices|null $presentationServices
	 */
	public function setPresentationServices(\Change\Presentation\PresentationServices $presentationServices = null)
	{
		$this->presentationServices = $presentationServices;
	}

	/**
	 * @api
	 * @return \Change\Presentation\PresentationServices|null
	 */
	public function getPresentationServices()
	{
		return $this->presentationServices;
	}

	/**
	 * @param \Change\Http\Request $request
	 */
	public function setRequest($request)
	{
		$this->request = $request;
	}

	/**
	 * @api
	 * @return \Change\Http\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param \Change\Http\AuthenticationInterface|null $authentication
	 */
	public function setAuthentication($authentication)
	{
		$this->authentication = $authentication;
	}

	/**
	 * @api
	 * @return \Change\Http\AuthenticationInterface|null
	 */
	public function getAuthentication()
	{
		return $this->authentication;
	}

	/**
	 * @param \Change\Http\AclInterface $acl
	 */
	public function setAcl($acl)
	{
		$this->acl = $acl;
	}

	/**
	 * @api
	 * @return \Change\Http\AclInterface
	 */
	public function getAcl()
	{
		return $this->acl;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @api
	 * @return \Change\Http\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @api
	 * @param callable|null $authorization
	 */
	public function setAuthorization($authorization)
	{
		$this->authorization = $authorization;
	}

	/**
	 * @api
	 * @return callable|null
	 */
	public function getAuthorization()
	{
		return $this->authorization;
	}

	/**
	 * @api
	 * @param callable|null $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @api
	 * @return callable|null
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @api
	 * @param \Change\Http\Result $result
	 */
	public function setResult($result)
	{
		$this->result = $result;
	}

	/**
	 * @api
	 * @return \Change\Http\Result
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