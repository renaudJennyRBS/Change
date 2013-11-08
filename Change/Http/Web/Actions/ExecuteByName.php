<?php
namespace Change\Http\Web\Actions;

use Change\Http\Web\Event;
use Zend\Authentication\Storage\Session;
use Zend\Http\Response as HttpResponse;

/**
* @name \Change\Http\Web\Actions\ExecuteByName
*/
class ExecuteByName
{
	/**
	 * Use Required Event Params:
	 * @param Event $event
	 * @throws \RuntimeException
	 */
	public function execute($event)
	{
		$defaultRedirectLocation = $event->getRequest()->getPost('redirectLocation', $event->getRequest()->getQuery('redirectLocation'));
		$defaultErrorLocation = $event->getRequest()->getPost('errorLocation', $event->getRequest()->getQuery('errorLocation', $defaultRedirectLocation));
		try
		{
			$action = $event->getParam('action');
			if (is_array($action) && count($action) === 3)
			{
				$className = '\\' . $action[0] . '\\' . $action[1] . '\\Http\\Web\\' .str_replace('/', '\\', $action[2]);
				if (class_exists($className))
				{
					$callable = new $className();
					if (is_callable($callable))
					{
						call_user_func($callable, $event);
					}
				}
			}

			$redirectLocation = $event->getParam('redirectLocation', $defaultRedirectLocation);
			if ($redirectLocation)
			{
				$result = new \Change\Http\Result(HttpResponse::STATUS_CODE_302);
				$result->setHeaderLocation($redirectLocation);
				$event->setResult($result);
				return;
			}

			$result = $event->getResult();
			if ($result === null)
			{
				$result = new \Change\Http\Web\Result\AjaxResult(array('Action not found' => $action));
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
				$event->setResult($result);
			}
		}
		catch (\Exception $e)
		{
			$result = new \Change\Http\Web\Result\AjaxResult(array('exception' => array('code' => $e->getCode(), 'message' => $e->getMessage())));
			$result->setHttpStatusCode((isset($e->httpStatusCode)) ? $e->httpStatusCode : HttpResponse::STATUS_CODE_500);
			if ($event->getApplication()->inDevelopmentMode())
			{
				$result->setEntry('trace', $e->getTraceAsString());
			}

			$errorLocation = $event->getParam('errorLocation', $defaultErrorLocation);
			if ($errorLocation)
			{
				$errorMessage = $result->toArray();

				$url = new \Zend\Uri\Http($errorLocation);
				$query = $url->getQueryAsArray();
				$query['errId'] = uniqid('err', true);
				$url->setQuery($query);

				$result = new \Change\Http\Result(HttpResponse::STATUS_CODE_302);
				$session = new \Zend\Session\Container('Change_Errors');
				$session[$query['errId']] = $errorMessage;
				$result->setHeaderLocation($url->toString());
			}
			$event->setResult($result);
		}
	}
}