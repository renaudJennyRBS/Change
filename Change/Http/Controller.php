<?php
namespace Change\Http;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Controller
 */
class Controller implements \Zend\EventManager\EventManagerAwareInterface
{
	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @var \Change\Http\ActionResolver
	 */
	protected $actionResolver;


	/**
	 * @var \Zend\EventManager\EventManager
	 */
	protected $eventManager;

	/**
	 * @param \Change\Application $application
	 */
	function __construct(\Change\Application $application)
	{
		$this->setApplication($application);
	}

	/**
	 * @param \Change\Application $application
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
	}

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * @param \Zend\EventManager\EventManager|\Zend\EventManager\EventManagerInterface $eventManager
	 * @return void
	 */
	public function setEventManager(\Zend\EventManager\EventManagerInterface $eventManager)
	{
		$this->eventManager = $eventManager;
	}

	/**
	 * @return \Zend\EventManager\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$eventManager = new \Zend\EventManager\EventManager('Http');
			$eventManager->setSharedManager($this->application->getSharedEventManager());
			$this->registerDefaultListeners($eventManager);
			$this->setEventManager($eventManager);
		}
		return $this->eventManager;
	}

	/**
	 * @param \Change\Http\ActionResolver $actionResolver
	 */
	public function setActionResolver(\Change\Http\ActionResolver $actionResolver)
	{
		$this->actionResolver = $actionResolver;
	}

	/**
	 * @return \Change\Http\ActionResolver
	 */
	public function getActionResolver()
	{
		if ($this->actionResolver === null)
		{
			$this->actionResolver = new \Change\Http\ActionResolver();
		}
		return $this->actionResolver;
	}

	/**
	 * @param Request $request
	 * @throws \RuntimeException
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function handle(Request $request)
	{
		$eventManager = $this->getEventManager();
		$event = $this->createEvent($request);
		try
		{
			$this->doSendRequest($eventManager, $event);
			if (!($event->getResult() instanceof Result))
			{
				$this->getActionResolver()->resolve($event);

				$this->doSendAction($eventManager, $event);

				$action = $event->getAction();

				if (is_callable($action))
				{
					call_user_func($action, $event);
				}

				$this->doSendResult($eventManager, $event);

				if (!($event->getResult() instanceof Result))
				{
					$notFound = new Result();
					$notFound->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
					$event->setResult($notFound);
				}
			}

			$this->doSendResponse($eventManager, $event);
		}
		catch (\Exception $exception)
		{
			$this->doSendException($eventManager, $event, $exception);
		}

		if ($event->getResponse() instanceof \Zend\Http\PhpEnvironment\Response)
		{
			return $event->getResponse();
		}

		return $this->getDefaultResponse($event);
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param \Change\Http\Event $event
	 */
	protected function doSendRequest($eventManager, \Change\Http\Event $event)
	{
		$eventManager->trigger(Event::EVENT_REQUEST, $this, $event);
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param \Change\Http\Event $event
	 */
	protected function doSendAction($eventManager, \Change\Http\Event $event)
	{
		$eventManager->trigger(Event::EVENT_ACTION, $this, $event);
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param \Change\Http\Event $event
	 */
	protected function doSendResult($eventManager, \Change\Http\Event $event)
	{
		$eventManager->trigger(Event::EVENT_RESULT, $this, $event);
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param \Change\Http\Event $event
	 */
	protected function doSendResponse($eventManager, \Change\Http\Event $event)
	{
		try
		{
			$eventManager->trigger(Event::EVENT_RESPONSE, $this, $event);
		}
		catch (\Exception $exception)
		{
			$event->setParam('Exception', $exception);
			if ($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->exception($exception);
			}
		}
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @param \Change\Http\Event $event
	 * @param \Exception $exception
	 */
	protected function doSendException($eventManager, $event, $exception)
	{
		try
		{
			$event->setParam('Exception', $exception);
			$eventManager->trigger(Event::EVENT_EXCEPTION, $this, $event);

			$this->doSendResponse($eventManager, $event);
		}
		catch (\Exception $e)
		{
			if ($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->exception($exception);
			}
		}
	}



	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 * @return void
	 */
	protected function registerDefaultListeners($eventManager)
	{

	}

	/**
	 * @param \Change\Http\Request $request
	 * @return \Change\Http\Event
	 */
	protected function createEvent($request)
	{
		$event = new \Change\Http\Event();
		$event->setRequest($request);
		$script = $request->getServer('SCRIPT_NAME');
		if (strpos($request->getRequestUri(), $script) !== 0)
		{
			$script = null;
		}
		$event->setUrlManager(new UrlManager($request->getUri(), $script));
		return $event;
	}

	/**
	 * @api
	 * @param \Change\Http\Request $request
	 * @param \Change\Http\Result $result
	 * @return boolean
	 */
	public function resultNotModified(\Change\Http\Request $request, $result)
	{
		if (($result instanceof \Change\Http\Result) && ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_200))
		{
			$etag = $result->getHeaderEtag();
			$ifNoneMatch = $request->getIfNoneMatch();
			if ($etag && $ifNoneMatch && $etag == $ifNoneMatch)
			{
				return true;
			}

			$lastModified = $result->getHeaderLastModified();
			$ifModifiedSince = $request->getIfModifiedSince();
			if ($lastModified && $ifModifiedSince && $lastModified <= $ifModifiedSince)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * @api
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function createResponse()
	{
		return new \Zend\Http\PhpEnvironment\Response();
	}

	/**
	 * @param \Change\Http\Event $event
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	protected function getDefaultResponse($event)
	{
		$response = $this->createResponse();
		$response->setStatusCode(HttpResponse::STATUS_CODE_500);
		return $response;
	}
}