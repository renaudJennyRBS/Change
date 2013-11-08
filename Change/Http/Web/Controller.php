<?php
namespace Change\Http\Web;

use Change\Documents\AbstractDocument;
use Change\Http\Request;
use Change\Http\Result;
use Change\Presentation\Interfaces\Page;
use Zend\Http\PhpEnvironment\Response;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Change\Http\Web\Controller
 */
class Controller extends \Change\Http\Controller
{
	/**
	 * @return string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return array('Http', 'Http.Web');
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach(Event::EVENT_RESULT, array($this, 'onDefaultResult'), 5);
		$eventManager->attach(Event::EVENT_RESPONSE, array($this, 'onDefaultResponse'), 5);
	}

	public function onDefaultRegisterServices(\Change\Http\Event $event)
	{
		parent::onDefaultRegisterServices($event);
		if ($event instanceof Event)
		{
			$applicationServices = $event->getApplicationServices();
			$event->getUrlManager()
				->setDbProvider($applicationServices->getDbProvider())
				->setTransactionManager($applicationServices->getTransactionManager())
				->setDocumentManager($applicationServices->getDocumentManager());
		}
	}

	/**
	 * @param $request
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
	public function onDefaultResult(Event $event)
	{
		$page = $event->getParam('page');
		if ($page instanceof Page)
		{
			$pageManager = $event->getApplicationServices()->getPageManager();
			$result = $pageManager->setHttpWebEvent($event)->getPageResult($page);
			if ($result)
			{
				$event->setResult($result);
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function onDefaultResponse($event)
	{
		$result = $event->getResult();
		if ($result instanceof Result)
		{
			$response = $event->getController()->createResponse();
			$response->setStatusCode($result->getHttpStatusCode());
			$response->getHeaders()->addHeaders($result->getHeaders());

			if ($result instanceof \Change\Http\Web\Result\Page)
			{
				$acceptHeader = $event->getRequest()->getHeader('Accept');
				if ($acceptHeader instanceof \Zend\Http\Header\Accept && $acceptHeader->hasMediaType('text/html'))
				{
					$response->setContent($result->toHtml());
				}
			}
			elseif ($result instanceof \Change\Http\Web\Result\Resource)
			{
				$response->setContent($result->getContent());
			}
			elseif ($result instanceof \Change\Http\Web\Result\AjaxResult)
			{
				$response->setContent(\Zend\Json\Json::encode($result->toArray()));
			}
			else
			{
				$callable = array($result, 'toHtml');
				if (is_callable($callable))
				{
					$response->setContent(call_user_func($callable));
				}
				else
				{
					$response->setContent(strval($result));
				}
			}
			$event->setResponse($response);
		}
	}

	/**
	 * @param Request $request
	 * @return boolean
	 */
	protected function acceptHtml($request)
	{
		$accept = $request->getHeader('Accept');
		if ($accept instanceof \Zend\Http\Header\Accept)
		{
			/* @var $acceptFieldValuePart \Zend\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart */
			foreach ($accept->getPrioritized() as $acceptFieldValuePart)
			{
				if ($acceptFieldValuePart->getSubtype() === 'html')
				{
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param Event $event
	 * @return Result
	 */
	public function notFound($event)
	{
		if ($this->acceptHtml($event->getRequest()))
		{
			$result = $this->getFunctionalResult($event, 'Error_404');
			if ($result !== null)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
				return $result;
			}
		}
		return parent::notFound($event);
	}

	public function unauthorized(\Change\Http\Event $event)
	{
		if ($this->acceptHtml($event->getRequest()))
		{
			$result = $this->getFunctionalResult($event, 'Error_401');
			if ($result !== null)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_401);
				return $result;
			}
		}
		return parent::unauthorized($event);
	}

	public function forbidden(\Change\Http\Event $event)
	{
		if ($this->acceptHtml($event->getRequest()))
		{
			$result = $this->getFunctionalResult($event, 'Error_403');
			if ($result !== null)
			{
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_403);
				return $result;
			}
		}
		return parent::forbidden($event);
	}

	/**
	 * @api
	 * @param Event $event
	 * @return Result
	 */
	public function error($event)
	{
		$accept = $event->getRequest()->getHeader('Accept');
		if ($accept instanceof \Zend\Http\Header\Accept)
		{
			/* @var $acceptFieldValuePart \Zend\Http\Header\Accept\FieldValuePart\AcceptFieldValuePart */
			foreach ($accept->getPrioritized() as $acceptFieldValuePart)
			{
				if ($acceptFieldValuePart->getSubtype() === 'html')
				{
					$result = $this->getFunctionalResult($event, 'Error_500');
					if ($result !== null)
					{
						$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
						return $result;
					}
					break;
				}
			}
		}
		return parent::error($event);
	}

	/**
	 * @param Event $event
	 * @param string $functionCode
	 * @return \Change\Http\Result
	 */
	protected function getFunctionalResult($event, $functionCode)
	{
		try
		{
			$page = null;
			$website = $event->getWebsite();
			if ($website instanceof AbstractDocument)
			{
				$em = $website->getEventManager();
				$args = array('functionCode' => $functionCode);
				$docEvent = new \Change\Documents\Events\Event('getPageByFunction', $website, $args);
				$em->trigger($docEvent);
				$page = $docEvent->getParam('page');
			}

			if (!($page instanceof Page))
			{
				$page = new \Change\Presentation\Themes\DefaultPage($event->getApplicationServices()
					->getThemeManager(), $functionCode);
			}

			$event->setParam('page', $page);
			$this->doSendResult($event);
			return $event->getResult();
		}
		catch (\Exception $e)
		{
			$event->getApplicationServices()->getLogging()->exception($e);
		}
		return null;
	}

	/**
	 * @param Event $event
	 * @return Response
	 */
	protected function getDefaultResponse($event)
	{
		$result = $this->error($event);
		$event->setResult($result);
		$this->onDefaultResponse($event);
		return $event->getResponse();
	}
}