<?php
namespace Change\Http\Rest;

use Change\Http\BaseResolver;
use Change\Http\Event;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Zend\Http\Response;

/**
 * @name \Change\Http\Rest\Resolver
 */
class Resolver extends BaseResolver
{
	/**
	 * @var array<string => string>
	 */
	protected $resolverClasses = array();

	public function __construct()
	{
		$this->addResolverClasses('resources', '\Change\Http\Rest\ResourcesResolver');
		$this->addResolverClasses('resourcestree', '\Change\Http\Rest\ResourcesTreeResolver');
		$this->addResolverClasses('blocks', '\Change\Http\Rest\BlocksResolver');
		$this->addResolverClasses('models', '\Change\Http\Rest\ModelsResolver');
		$this->addResolverClasses('query', '\Change\Http\Rest\QueryResolver');
		$this->addResolverClasses('storage', '\Change\Http\Rest\StorageResolver');
		$this->addResolverClasses('actions', '\Change\Http\Rest\ActionsResolver');
		$this->addResolverClasses('jobs', '\Change\Http\Rest\JobsResolver');
		$this->addResolverClasses('commands', '\Change\Http\Rest\CommandsResolver');
	}

	/**
	 * @param string $name
	 * @param string $className
	 */
	public function addResolverClasses($name, $className)
	{
		$this->resolverClasses[$name] = $className;
	}

	/**
	 * @param string $name
	 * @return ActionsResolver|ResourcesResolver|ResourcesTreeResolver|BlocksResolver|ModelsResolver|null
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
	 * @return \Change\Http\Rest\ActionsResolver
	 */
	public function getActionsResolver()
	{
		return $this->getResolverByName('actions');
	}

	/**
	 * Set Event params: pathParts, isDirectory
	 * @param Event $event
	 * @return void
	 */
	public function resolve($event)
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

		$pathParts = array_slice(explode('/', $path), 1);
		$event->setParam('pathInfo', implode('/', $pathParts));
		if (end($pathParts) === '')
		{
			array_pop($pathParts);
			$isDirectory = true;
		}
		else
		{
			$isDirectory = false;
		}
		$event->setParam('isDirectory', $isDirectory);
		$event->setParam('pathParts', $pathParts);

		if (count($pathParts) !== 0)
		{
			$resolver = $this->getResolverByName($pathParts[0]);
			if ($resolver)
			{
				array_shift($pathParts);
				$resolver->resolve($event, $pathParts, $request->getMethod());
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
			$result = $event->getController()->notAllowedError($request->getMethod(), array(Request::METHOD_GET));
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
}