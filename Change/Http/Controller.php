<?php
namespace Change\Http;

use Change\Application;
use Change\Services\ApplicationServices;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Controller
 */
class Controller implements \Zend\EventManager\EventsCapableInterface
{
	use \Change\Events\EventsCapableTrait;

	/**
	 * @var Application
	 */
	protected $application;

	/**
	 * @var BaseResolver
	 */
	protected $actionResolver;

	/**
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->setApplication($application);
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
		$classes = array();
		foreach ($this->getEventManagerIdentifier() as $name)
		{
			$entry = $config->getEntry('Change/Events/' . str_replace('.', '/', $name), array());
			if (is_array($entry))
			{
				foreach ($entry as $className)
				{
					if (is_string($className))
					{
						$classes[] = $className;
					}
				}
			}
		}
		return array_unique($classes);
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		$eventManager->attach('registerServices', array($this, 'onDefaultRegisterServices'), 5);
	}

	/**
	 * @param BaseResolver $actionResolver
	 */
	public function setActionResolver(BaseResolver $actionResolver)
	{
		$this->actionResolver = $actionResolver;
	}

	/**
	 * @return BaseResolver
	 */
	public function getActionResolver()
	{
		if ($this->actionResolver === null)
		{
			$this->actionResolver = new BaseResolver();
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
		$this->eventManagerFactory = new \Change\Events\EventManagerFactory($this->application);
		$event = $this->createEvent($request);
		try
		{
			$this->doSendRegisterServices($event);

			$this->doSendRequest($event);

			if (!($event->getResult() instanceof Result))
			{
				$this->getActionResolver()->resolve($event);

				$this->doSendAction($event);

				if ($this->checkAuthorization($event))
				{
					$action = $event->getAction();
					if (is_callable($action))
					{
						call_user_func($action, $event);
					}
				}

				$this->doSendResult($event);

				if (!($event->getResult() instanceof Result))
				{
					$this->notFound($event);
				}
			}

			$this->doSendResponse($event);
		}
		catch (\Exception $exception)
		{
			$this->doSendException($event, $exception);
		}

		if ($event->getResponse() instanceof Response)
		{
			return $event->getResponse();
		}

		return $this->getDefaultResponse($event);
	}

	/**
	 * @param Event $event
	 * @return boolean
	 */
	protected function checkAuthorization(Event $event)
	{
		$authorization = $event->getAuthorization();
		if (is_callable($authorization))
		{
			$permissionsManager = $event->getPermissionsManager();
			$this->doSendAuthenticate($event);
			if (!$permissionsManager->allow())
			{
				$user = $event->getAuthenticationManager()->getCurrentUser();
				$permissionsManager->setUser($user);
				$authorized = call_user_func($authorization, $event);
				if (!$authorized)
				{
					if ($user->authenticated())
					{
						$this->forbidden($event);
						return false;
					}
					else
					{
						$this->unauthorized($event);
						return false;
					}
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
	 * @param string $notAllowed
	 * @param string[] $allow
	 * @return Result
	 */
	public function notAllowedError($notAllowed, array $allow)
	{
		$result = new Result(HttpResponse::STATUS_CODE_405);
		$header = \Zend\Http\Header\Allow::fromString('allow: ' . implode(', ', $allow));
		$result->getHeaders()->addHeader($header);
		return $result;
	}

	/**
	 * @param Event $event
	 */
	protected function doSendRegisterServices(Event $event)
	{
		$event->setName('registerServices');
		$event->setTarget($this);
		$event->setParam('eventManagerFactory', $this->eventManagerFactory);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @param Event $event
	 */
	protected function doSendRequest(Event $event)
	{
		$event->setName(Event::EVENT_REQUEST);
		$event->setTarget($this);
		$results = $this->getEventManager()->trigger($event, function ($result)
		{
			return ($result instanceof Result);
		});
		if ($results->stopped() && ($results->last() instanceof Result))
		{
			$event->setResult($results->last());
		}
	}

	/**
	 * @param Event $event
	 */
	protected function doSendAuthenticate(Event $event)
	{
		$event->setName(Event::EVENT_AUTHENTICATE);
		$event->setTarget($this);
		$this->getEventManager()->trigger($event);
	}

	/**
	 * @param Event $event
	 */
	protected function doSendAction(Event $event)
	{
		$event->setName(Event::EVENT_ACTION);
		$event->setTarget($this);

		$results = $this->getEventManager()->trigger($event, function ($result)
		{
			return ($result !== null) && is_callable($result);
		});
		$last = $results->last();
		if ($results->stopped() && ($last !== null && is_callable($last)))
		{
			$event->setAction($last);
		}
	}

	/**
	 * @param Event $event
	 */
	protected function doSendResult(Event $event)
	{
		$event->setName(Event::EVENT_RESULT);
		$event->setTarget($this);
		$results = $this->getEventManager()->trigger($event, function ($result)
		{
			return ($result instanceof Result);
		});
		if ($results->stopped() && ($results->last() instanceof Result))
		{
			$event->setResult($results->last());
		}
	}

	/**
	 * @param Event $event
	 */
	protected function doSendResponse(Event $event)
	{
		try
		{
			$event->setName(Event::EVENT_RESPONSE);
			$event->setTarget($this);

			$results = $this->getEventManager()->trigger($event, function ($result)
			{
				return ($result instanceof Response);
			});
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
				$this->doSendException($event, $exception);
			}
		}
	}

	/**
	 * @param Event $event
	 * @param \Exception $exception
	 */
	protected function doSendException($event, $exception)
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
			$this->getEventManager()->trigger($event);
			$this->doSendResponse($event);
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
	 * @param Event $event
	 */
	public function onDefaultRegisterServices(Event $event)
	{
		$event->getServices()->set('applicationServices', new ApplicationServices($this->application, $event->getParam('eventManagerFactory')));
	}
}