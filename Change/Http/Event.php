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
	 * @var \Change\Http\Request
	 */
	protected $request;

	/**
	 * @var \Change\Http\UrlManager
	 */
	protected $urlManager;

	/**
	 * @var \Closure
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
	 * @var string
	 */
	protected $LCID;

	/**
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
	public function setApplicationServices($applicationServices)
	{
		$this->applicationServices = $applicationServices;
	}

	/**
	 * @return \Change\Application\ApplicationServices|null
	 */
	public function getApplicationServices()
	{
		return $this->applicationServices;
	}

	/**
	 * @param \Change\Documents\DocumentServices|null $documentServices
	 */
	public function setDocumentServices($documentServices)
	{
		$this->documentServices = $documentServices;
	}

	/**
	 * @return \Change\Documents\DocumentServices|null
	 */
	public function getDocumentServices()
	{
		return $this->documentServices;
	}

	/**
	 * @param \Change\Http\Request $request
	 */
	public function setRequest($request)
	{
		$this->request = $request;
	}

	/**
	 * @return \Change\Http\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @param \Change\Http\UrlManager $urlManager
	 */
	public function setUrlManager($urlManager)
	{
		$this->urlManager = $urlManager;
	}

	/**
	 * @return \Change\Http\UrlManager
	 */
	public function getUrlManager()
	{
		return $this->urlManager;
	}

	/**
	 * @param string $LCID
	 */
	public function setLCID($LCID)
	{
		$this->LCID = $LCID;
	}

	/**
	 * @throws \RuntimeException
	 * @return string
	 */
	public function getLCID()
	{
		if (!$this->LCID)
		{
			throw new \RuntimeException('LCID not defined', 70002);
		}
		return $this->LCID;
	}

	/**
	 * @param \Closure|null $action
	 */
	public function setAction($action)
	{
		$this->action = $action;
	}

	/**
	 * @return \Closure|null
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * @param \Change\Http\Result $result
	 */
	public function setResult($result)
	{
		$this->result = $result;
	}

	/**
	 * @return \Change\Http\Result
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @param \Zend\Http\PhpEnvironment\Response|null $response
	 */
	public function setResponse($response)
	{
		$this->response = $response;
	}

	/**
	 * @return \Zend\Http\PhpEnvironment\Response|Null
	 */
	public function getResponse()
	{
		return $this->response;
	}
}