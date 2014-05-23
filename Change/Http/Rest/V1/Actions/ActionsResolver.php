<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Actions;

use Change\Http\Rest\Request;
use Change\Http\Rest\V1\DiscoverNameSpace;
use Change\Http\Rest\V1\Resolver;

/**
 * @name \Change\Http\Rest\V1\Actions\ActionsResolver
 */
class ActionsResolver implements \Change\Http\Rest\V1\NameSpaceDiscoverInterface
{
	const RESOLVER_NAME = 'actions';

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @var array
	 */
	protected $actionClasses = [];

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
		$this->registerActionClass('collectionItems', '\Change\Http\Rest\V1\Actions\GetCollectionItems');
		$this->registerActionClass('collectionCodes', '\Change\Http\Rest\V1\Actions\GetCollectionCodes');
		$this->registerActionClass('renderRichText', '\Change\Http\Rest\V1\Actions\RenderRichText');
		$this->registerActionClass('activate', '\Change\Http\Rest\V1\Actions\Activation');
		$this->registerActionClass('deactivate', '\Change\Http\Rest\V1\Actions\Deactivation');
		$this->registerActionClass('filters', '\Change\Http\Rest\V1\Actions\DocumentFilters');
	}

	/**
	 * @return array
	 */
	public function getActionClasses()
	{
		return $this->actionClasses;
	}

	/**
	 * @param $actionName
	 * @param $class
	 */
	public function registerActionClass($actionName, $class)
	{
		$this->actionClasses[$actionName] = $class;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		$namespaces = [];
		$names = array_keys($this->actionClasses);
		$base = implode('.', $namespaceParts);
		foreach ($names as $name)
		{
			$namespaces[] = $base . '.' . $name;
		}
		return $namespaces;
	}

	/**
	 * Set Event params: resourcesActionName, documentId, LCID
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$nbParts = count($resourceParts);
		if ($nbParts == 0 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, static::RESOLVER_NAME);
			$event->setParam('namespace', implode('.', $resourceParts));
			$event->setParam('resolver', $this);
			$action = function ($event)
			{
				$action = new DiscoverNameSpace();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif ($nbParts == 1)
		{
			$actionClasses = $this->getActionClasses();
			$actionName = $resourceParts[0];
			if (!isset($actionClasses[$actionName]))
			{
				//Action not found
				return;
			}
			$actionClass = $actionClasses[$actionName];
			if (!class_exists($actionClass))
			{
				//Action Class not found
				return;
			}
			$instance = new $actionClass();
			$callable = [$instance, 'execute'];
			if (!is_callable($callable))
			{
				//Callable Not found
				return;
			}
			$event->setParam('actionName', $actionName);
			$event->setAction(function($event) use($callable) {call_user_func($callable, $event);});
			return;
		}
	}
}