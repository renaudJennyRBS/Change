<?php
namespace Change\Http;

use Change\Application;
use Change\Events\EventsCapableTrait;
use Zend\EventManager\EventManager;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Controller
 */
class Controller implements \Zend\EventManager\EventsCapableInterface
{
	use EventsCapableTrait {
		EventsCapableTrait::attachEvents as defaultAttachEvents;
	}

	/**
	 * @var Application
	 */
	protected $application;

	/**
	 * @var ActionResolver
	 */
	protected $actionResolver;


	/**
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->setApplication($application);
		$this->setSharedEventManager($application->getSharedEventManager());
	}

	/**
	 * @param \Change\Application $application
	 */
	public function setApplication(Application $application)
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
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Http');
	}

	/**
	 * @return string[]
	 */
	protected function getListenerAggregateClassNames()
	{
		$config = $this->getApplication()->getConfiguration();
		$identifiers = $this->getEventManagerIdentifier();
		return $config->getEntry('Change/Events/' . $identifiers[0], array());
	}

	/**
	 * @param EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->defaultAttachEvents($eventManager);
	}

	/**
	 * @param ActionResolver $actionResolver
	 */
	public function setActionResolver(ActionResolver $actionResolver)
	{
		$this->actionResolver = $actionResolver;
	}

	/**
	 * @return ActionResolver
	 */
	public function getActionResolver()
	{
		if ($this->actionResolver === null)
		{
			$this->actionResolver = new ActionResolver();
		}
		return $this->actionResolver;
	}

	/**
	 * @param Request $request
	 * @throws \RuntimeException
	 * @return Response
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

				if ($this->checkAuthorization($eventManager, $event))
				{
					$action = $event->getAction();
					if (is_callable($action))
					{
						call_user_func($action, $event);
					}
				}

				$this->doSendResult($eventManager, $event);

				if (!($event->getResult() instanceof Result))
				{
					$this->notFound($event);
				}
			}

			$this->doSendResponse($eventManager, $event);
		}
		catch (\Exception $exception)
		{
			$this->doSendException($eventManager, $event, $exception);
		}

		if ($event->getResponse() instanceof Response)
		{
			return $event->getResponse();
		}

		return $this->getDefaultResponse($event);
	}

	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 * @return boolean
	 */
	protected function checkAuthorization(EventManager $eventManager, Event $event)
	{
		$authorization = $event->getAuthorization();
		if (is_callable($authorization))
		{
			$permissionsManager = $event->getPermissionsManager();
			$this->doSendAuthenticate($eventManager, $event);
			if (!$permissionsManager->allow())
			{
				$user = $event->getAuthenticationManager()->getCurrentUser();
				if ($user->authenticated())
				{
					$permissionsManager->setUser($user);
					$authorized = call_user_func($authorization, $event);
					if (!$authorized)
					{
						$this->forbidden($event);
						return false;
					}
				}
				else
				{
					$this->unauthorized($event);
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @api
	 * @param Event $event
	 * @return Result
	 */
	public function notFound($event)
	{
		$notFound = new Result();
		$notFound->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
		$event->setResult($notFound);
		return $notFound;
	}

	/**
	 * @api
	 * @param Event $event
	 * @return Result
	 */
	public function unauthorized(Event $event)
	{
		$unauthorized = new Result();
		$unauthorized->setHttpStatusCode(HttpResponse::STATUS_CODE_401);
		$event->setResult($unauthorized);
		return $unauthorized;
	}

	/**
	 * @api
	 * @param Event $event
	 * @return Result
	 */
	public function forbidden(Event $event)
	{
		$forbidden = new Result();
		$forbidden->setHttpStatusCode(HttpResponse::STATUS_CODE_403);
		$event->setResult($forbidden);
		return $forbidden;
	}

	/**
	 * @api
	 * @param Event $event
	 * @return Result
	 */
	public function error($event)
	{
		$error = new Result();
		$error->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
		$event->setResult($error);
		return $error;
	}

	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 */
	protected function doSendRequest($eventManager, Event $event)
	{
		$event->setName(Event::EVENT_REQUEST);
		$event->setTarget($this);

		$results = $eventManager->trigger($event, function($result) {return ($result instanceof Result);});
		if ($results->stopped() && ($results->last() instanceof Result))
		{
			$event->setResult($results->last());
		}
	}


	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 */
	protected function doSendAuthenticate($eventManager, Event $event)
	{
		$event->setName(Event::EVENT_AUTHENTICATE);
		$event->setTarget($this);
		$eventManager->trigger($event);
	}

	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 */
	protected function doSendAction($eventManager, Event $event)
	{
		$event->setName(Event::EVENT_ACTION);
		$event->setTarget($this);

		$results = $eventManager->trigger($event, function($result) {return ($result !== null) && is_callable($result);});
		$last = $results->last();
		if ($results->stopped() && ($last !== null && is_callable($last)))
		{
			$event->setAction($last);
		}
	}

	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 */
	protected function doSendResult($eventManager, Event $event)
	{
		$event->setName(Event::EVENT_RESULT);
		$event->setTarget($this);

		$results = $eventManager->trigger($event, function($result) {return ($result instanceof Result);});
		if ($results->stopped() && ($results->last() instanceof Result))
		{
			$event->setResult($results->last());
		}
	}

	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 */
	protected function doSendResponse($eventManager, Event $event)
	{
		try
		{
			$event->setName(Event::EVENT_RESPONSE);
			$event->setTarget($this);

			$results = $eventManager->trigger($event, function($result) {return ($result instanceof Response);});
			if ($results->stopped() && ($results->last() instanceof Response))
			{
				$event->setResponse($results->last());
			}

		}
		catch (\Exception $exception)
		{
			if ($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->exception($exception);
			}

			if (!($event->getParam('Exception') instanceof \Exception))
			{
				$this->doSendException($eventManager, $event, $exception);
			}
		}
	}

	/**
	 * @param EventManager $eventManager
	 * @param Event $event
	 * @param \Exception $exception
	 */
	protected function doSendException($eventManager, $event, $exception)
	{
		try
		{
			if ($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->exception($exception);
			}

			$event->setParam('Exception', $exception);
			$event->setName(Event::EVENT_EXCEPTION);
			$event->setTarget($this);
			$eventManager->trigger($event);

			$this->doSendResponse($eventManager, $event);
		}
		catch (\Exception $e)
		{
			if ($event->getApplicationServices())
			{
				$event->getApplicationServices()->getLogging()->exception($e);
			}
		}
	}

	/**
	 * @param Request $request
	 * @return Event
	 */
	protected function createEvent($request)
	{
		$event = new Event();
		$event->setRequest($request);

		$script = $request->getServer('SCRIPT_NAME');
		if (strpos($request->getRequestUri(), $script) !== 0)
		{
			$script = null;
		}

		$urlManager = new UrlManager($request->getUri(), $script);
		$event->setUrlManager($urlManager);
		return $event;
	}

	/**
	 * @api
	 * @param Request $request
	 * @param Result $result
	 * @return boolean
	 */
	public function resultNotModified(Request $request, $result)
	{
		if (($result instanceof Result) && ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_200))
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
	 * @return Response
	 */
	public function createResponse()
	{
		return new Response();
	}

	/**
	 * @param Event $event
	 * @return Response
	 */
	protected function getDefaultResponse($event)
	{
		$result = $this->error($event);
		$response = $this->createResponse();
		$response->setStatusCode($result->getHttpStatusCode());
		$response->setHeaders($result->getHeaders());
		return $response;
	}
}