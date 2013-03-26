<?php
namespace Change\Http\Rest;

use Change\Http\ActionResolver;
use Change\Http\Event;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Result\ErrorResult;
use Zend\Http\Response;

/**
 * @name \Change\Http\Rest\Resolver
 */
class Resolver extends ActionResolver
{
	protected $resourceActionClasses = array();

	function __construct()
	{
		$this->resourceActionClasses = array(
			'startValidation' => '\Change\Http\Rest\Actions\StartValidation',
			'startPublication' => '\Change\Http\Rest\Actions\StartPublication',
			'deactivate' => '\Change\Http\Rest\Actions\Deactivate',
			'activate' => '\Change\Http\Rest\Actions\Activate',
			'getCorrection' => '\Change\Http\Rest\Actions\GetCorrection',
			'startCorrectionValidation' => '\Change\Http\Rest\Actions\StartCorrectionValidation',
			'startCorrectionPublication' => '\Change\Http\Rest\Actions\StartCorrectionPublication');
	}

	/**
	 * @return array
	 */
	public function getResourceActionClasses()
	{
		return $this->resourceActionClasses;
	}

	/**
	 * @param $actionName
	 * @param $class
	 */
	public function registerActionClass($actionName, $class)
	{
		$this->resourceActionClasses[$actionName] = $class;
	}

	/**
	 * Set Event params: namespace, isDirectory
	 * @param Event $event
	 * @return void
	 */
	public function resolve(Event $event)
	{
		$request = $event->getRequest();
		$path = $request->getPath();
		if (strpos($path, '//') !== false)
		{

			return;
		}
		$nameSpaces = array_slice(explode('/', $path), 1);
		if (end($nameSpaces) === '')
		{
			array_pop($nameSpaces);
			$event->setParam('isDirectory', true);
		}
		else
		{
			$event->setParam('isDirectory', false);
		}
		if (count($nameSpaces) !== 0)
		{
			switch ($nameSpaces[0])
			{
				case 'resources' :
					$resourcesResolver = new ResourcesResolver($this);
					$resourcesResolver->resolve($event, array_slice($nameSpaces, 1), $request->getMethod());
					break;
				case 'resourcesactions' :
					$resourcesResolver = new ResourcesActionsResolver($this);
					$resourcesResolver->resolve($event, array_slice($nameSpaces, 1), $request->getMethod());
					break;
				case 'resourcestree' :
					$resourcesResolver = new ResourcesTreeResolver($this);
					$resourcesResolver->resolve($event, array_slice($nameSpaces, 1), $request->getMethod());
					break;
			}
		}

		if ($event->getAction() === null && $event->getParam('isDirectory'))
		{
			if ($request->getMethod() === 'GET')
			{
				$event->setParam('namespace', implode('.', $nameSpaces));
				$event->setParam('Resolver', $this);
				$action = new DiscoverNameSpace();
				$event->setAction(function($event) use($action) {$action->execute($event);});
				return;
			}

			$result = $this->buildNotAllowedError($request->getMethod(), array(Request::METHOD_GET));
			$event->setResult($result);
			return;
		}
	}

	/**
	 * @param string $notAllowed
	 * @param string[] $allow
	 * @return Result\ErrorResult
	 */
	public function buildNotAllowedError($notAllowed, array $allow)
	{
		$msg = 'Method not allowed: ' . $notAllowed;
		$result = new ErrorResult('METHOD-ERROR', $msg, Response::STATUS_CODE_405);
		$header = \Zend\Http\Header\Allow::fromString('allow: ' . implode(', ', $allow));
		$result->getHeaders()->addHeader($header);
		$result->addDataValue('allow', $allow);
		return $result;
	}

	/**
	 * @param Event $event
	 * @param mixed $resource
	 * @param string $privilege
	 */
	public function setAuthorisation($event, $resource, $privilege)
	{
		$authorisation = function(Event $event) use ($resource, $privilege)
		{
			$hasPrivilege = $event->getAcl()->hasPrivilege($resource, $privilege);
			$event->getApplicationServices()->getLogging()->info('hasPrivilege(' . var_export($resource, true) . ', '.  var_export($privilege, true).'): ' . var_export($hasPrivilege, true));
			return $hasPrivilege;
		};
		$event->setAuthorization($authorisation);
	}
}