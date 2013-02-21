<?php
namespace Change\Http\Rest;

use Zend\Http\Response as HttpResponse;
use Change\Http\Rest\Result\ErrorResult;

/**
 * @name \Change\Http\Rest\Controller
 */
class Controller extends \Change\Http\Controller
{

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 * @return void
	 */
	protected function registerDefaultListeners($eventManager)
	{
		$eventManager->attach(\Change\Http\Event::EVENT_EXCEPTION, array($this, 'onException'), 5);
		$eventManager->attach(\Change\Http\Event::EVENT_RESPONSE, array($this, 'onDefaultJsonResponse'), 5);
	}

	/**
	 * @param \Change\Http\Request $request
	 * @return \Change\Http\Event
	 */
	protected function createEvent($request)
	{
		$event = parent::createEvent($request);
		$event->setApplicationServices(new \Change\Application\ApplicationServices($this->getApplication()));
		$event->setDocumentServices(new \Change\Documents\DocumentServices($event->getApplicationServices()));

		$header = $request->getHeader('Accept-Language');
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$event->setLCID($this->findLCID($header, $i18nManager));

		return $event;
	}

	/**
	 * @param \Zend\Http\Header\HeaderInterface $header
	 * @param \Change\I18n\I18nManager $i18nManager
	 * @return string
	 */
	protected function findLCID($header, $i18nManager)
	{
		$LCID = null;
		if ($header instanceof \Zend\Http\Header\AcceptLanguage)
		{
			foreach ($header->getPrioritized() as $part)
			{
				/* @var $part \Zend\Http\Header\Accept\FieldValuePart\LanguageFieldValuePart */
				$language = $part->getLanguage();

				if (strlen($language) === 2)
				{
					$testLCID = strtolower($language) . '_' . strtoupper($language);
				}
				elseif (strlen($language) === 5)
				{
					$testLCID = strtolower(substr($language, 0, 2)) . '_' . strtoupper(substr($language, 3, 2));
				}
				else
				{
					continue;
				}

				if ($i18nManager->isSupportedLCID($testLCID))
				{
					$LCID = $testLCID;
					break;
				}
			}
		}
		return $LCID ? $LCID : $i18nManager->getDefaultLCID();
	}

	/**
	 * @api
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	public function createResponse()
	{
		$response = parent::createResponse();
		$response->getHeaders()->addHeaderLine('Content-Type: application/json');
		return $response;
	}


	/**
	 * @param \Change\Http\Event $event
	 * @return \Zend\Http\PhpEnvironment\Response
	 */
	protected function getDefaultResponse($event)
	{

		$response = $this->createResponse();
		$response->setStatusCode(HttpResponse::STATUS_CODE_500);
		$content = array('code' => 'ERROR-GENERIC', 'message' => 'Generic error');
		$response->setContent(json_encode($content));
		return $response;
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function onException($event)
	{
		/* @var $exception \Exception */
		$exception = $event->getParam('Exception');
		$result = $event->getResult();

		if (!($result instanceof ErrorResult))
		{
			$error = new ErrorResult($exception);
			if ($event->getResult() instanceof \Change\Http\Result)
			{
				$result = $event->getResult();
				$error->setHttpStatusCode($result->getHttpStatusCode());
				if ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_404)
				{
					$error->setErrorCode('NOT_FOUND');
					$error->setErrorMessage($event->getRequest()->getPath());
				}
			}

			$event->setResult($error);
			$event->setResponse(null);
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function onDefaultJsonResponse($event)
	{
		$result = $event->getResult();
		if ($result instanceof \Change\Http\Result)
		{
			$response = $event->getController()->createResponse();
			$response->getHeaders()->addHeaders($result->getHeaders());

			$response->setStatusCode($result->getHttpStatusCode());
			$event->setResponse($response);

			if ($response->getStatusCode() === HttpResponse::STATUS_CODE_200)
			{
				$lastModified = $result->getHeaderLastModified();
				$ifModifiedSince = $event->getRequest()->getIfModifiedSince();
				if ($lastModified && $ifModifiedSince && $lastModified <= $ifModifiedSince)
				{
					$response->setStatusCode(HttpResponse::STATUS_CODE_304);
					return;
				}
			}

			$callable = array($result, 'toArray');
			if (is_callable($callable))
			{
				$data = call_user_func($callable);
				$response->setContent(json_encode($data));
			}
			elseif ($result->getHttpStatusCode() === HttpResponse::STATUS_CODE_404)
			{
				$error = new ErrorResult('NOT_FOUND', $event->getRequest()->getPath());
				$response->setContent(json_encode($error->toArray()));
			}
		}
	}
}