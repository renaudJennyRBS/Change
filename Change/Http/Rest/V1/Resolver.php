<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1;

use Change\Http\BaseResolver;
use Change\Http\Event;
use Change\Http\Rest\Request;
use Zend\Http\Response;

/**
 * @name \Change\Http\Rest\V1\Resolver
 */
class Resolver extends BaseResolver implements NameSpaceDiscoverInterface
{
	/**
	 * @var array<string => string>
	 */
	protected $resolverClasses = array();

	public function __construct()
	{
		$this->addResolverClasses('resources', '\Change\Http\Rest\V1\Resources\ResourcesResolver');
		$this->addResolverClasses('resourcestree', '\Change\Http\Rest\V1\ResourcesTree\ResourcesTreeResolver');
		$this->addResolverClasses('blocks', '\Change\Http\Rest\V1\Blocks\BlocksResolver');
		$this->addResolverClasses('models', '\Change\Http\Rest\V1\Models\ModelsResolver');
		$this->addResolverClasses('query', '\Change\Http\Rest\V1\Query\QueryResolver');
		$this->addResolverClasses('storage', '\Change\Http\Rest\V1\Storage\StorageResolver');
		$this->addResolverClasses('actions', '\Change\Http\Rest\V1\Actions\ActionsResolver');
		$this->addResolverClasses('jobs', '\Change\Http\Rest\V1\Jobs\JobsResolver');
		$this->addResolverClasses('commands', '\Change\Http\Rest\V1\Commands\CommandsResolver');
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
	 * @return NameSpaceDiscoverInterface|null
	 */
	public function getResolverByName($name)
	{
		if (isset($this->resolverClasses[$name]))
		{
			$resolver = $this->resolverClasses[$name];
			if (is_string($resolver) && class_exists($resolver))
			{
				$resolver = new $resolver($this);
				$this->resolverClasses[$name] = $resolver;
			}
			return $resolver;
		}
		return null;
	}

	/**
	 * @return \Change\Http\Rest\V1\Actions\ActionsResolver
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
		$path = trim(strval($request->getPath()));
		if (empty($path) !== false)
		{
			$path = '/';
		}
		$pathParts = array_slice(explode('/', $path), 1);
		$pathInfo = implode('/', $pathParts);
		$event->getApplication()->getLogging()->info('Rest Event pathInfo: ' .var_export($pathInfo, true));
		$event->setParam('pathInfo', $pathInfo);
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
			$callable = [$resolver, 'resolve'];
			if (is_object($resolver) && is_callable($callable))
			{
				array_shift($pathParts);
				call_user_func($callable, $event, $pathParts, $request->getMethod());
			}
		}
		elseif ($request->isGet())
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