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
	/**
	 * @var array<string => string>
	 */
	protected $resolverClasses = array();

	public function __construct()
	{
		$this->addResolverClasses('resources', '\Change\Http\Rest\ResourcesResolver');
		$this->addResolverClasses('resourcesactions', '\Change\Http\Rest\ResourcesActionsResolver');
		$this->addResolverClasses('resourcestree', '\Change\Http\Rest\ResourcesTreeResolver');
		$this->addResolverClasses('blocks', '\Change\Http\Rest\BlocksResolver');
		$this->addResolverClasses('models', '\Change\Http\Rest\ModelsResolver');
		$this->addResolverClasses('query', '\Change\Http\Rest\QueryResolver');
	}

	/**
	 * @param string $name
	 * @param string $className
	 */
	protected function addResolverClasses($name, $className)
	{
		$this->resolverClasses[$name] = $className;
	}

	/**
	 * @param string $name
	 * @return ResourcesActionsResolver|ResourcesResolver|ResourcesTreeResolver|BlocksResolver|ModelsResolver|null
	 */
	public function getResolverByName($name)
	{
		if (isset($this->resolverClasses[$name]))
		{
			$resolver = $this->resolverClasses[$name];
			if (is_string($resolver))
			{
				$resolver = new $resolver($this);
				$this->resolverClasses[$name] = $resolver;
			}
			return $resolver;
		}
		return null;
	}

	/**
	 * @param string $actionName
	 * @param string $class
	 */
	public function registerActionClass($actionName, $class)
	{
		$resolver = $this->getResolverByName('resourcesactions');
		$resolver->registerActionClass($actionName, $class);
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
		elseif ($path === '/rest.php')
		{
			$path = '/';
		}

		$namespaceParts = array_slice(explode('/', $path), 1);
		if (end($namespaceParts) === '')
		{
			array_pop($namespaceParts);
			$event->setParam('isDirectory', true);
		}
		else
		{
			$event->setParam('isDirectory', false);
		}

		if (count($namespaceParts) !== 0)
		{
			$resolver = $this->getResolverByName($namespaceParts[0]);
			if ($resolver)
			{
				array_shift($namespaceParts);
				$resolver->resolve($event, $namespaceParts, $request->getMethod());
			}
		}
		elseif ($request->getMethod() === 'GET')
		{
			$event->setParam('namespace', '');
			$event->setParam('resolver', $this);
			$action = function($event) {
				$action = new DiscoverNameSpace();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		else
		{
			$result = $this->buildNotAllowedError($request->getMethod(), array(Request::METHOD_GET));
			$event->setResult($result);
			return;
		}
	}

	/**
	 * @param Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array_keys($this->resolverClasses);
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