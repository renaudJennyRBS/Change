<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin;

/**
 * @name \Rbs\Admin\RoutesHelper
 */
class RoutesHelper
{
	protected $routes;

	/**
	 * @var array
	 */
	protected $namedRoutes;

	public function __construct($routes)
	{
		$this->routes = $routes;
	}

	public function debug__getRoutes()
	{
		return $this->routes;
	}

	public function setRoutes($routes)
	{
		$this->routes = $routes;
		if ($this->namedRoutes !== null)
		{
			$this->resetNamedRoutes();
		}
	}

	/**
	 * @param string $model
	 * @param string $name
	 * @return array|null
	 */
	public function getNamedRoute($model, $name)
	{
		if ($this->namedRoutes === null)
		{
			$this->resetNamedRoutes();
		}
		if (isset($this->namedRoutes[$model][$name]))
		{
			return $this->namedRoutes[$model][$name];
		}
		return null;
	}

	protected function resetNamedRoutes()
	{
		$this->namedRoutes = [];
		foreach ($this->routes as $path => $route)
		{
			if (isset($route['name']) && (isset($route['module'])) || isset($route['model']))
			{
				$key = isset($route['model']) ? $route['model'] : $route['module'];
				$route['path'] = $path;
				$this->namedRoutes[$key][$route['name']] = $route;
			}
		}
	}

	/**
	 * filter $routes to get only the $names named ones
	 * @param array $names
	 * @return array
	 */
	public function getRoutesWithNames($names)
	{
		return array_filter($this->routes, function ($route) use ($names) {
			return isset($route['name']) && isset($route['model']) && in_array($route['name'], $names);
		});
	}

	/**
	 * get diff from two routes array
	 * return all routes present in $routesA and not in $routesB comparing with key $compareKey
	 * @param array $routesA
	 * @param array $routesB
	 * @param string $compareKey
	 * @return array
	 */
	public function getRoutesDiff($routesA, $routesB, $compareKey)
	{
		return array_filter($routesA, function ($routeA) use ($routesB, $compareKey) {
			foreach ($routesB as $routeB)
			{
				if ($routeA[$compareKey] === $routeB[$compareKey])
				{
					return false;
				}
			}
			return true;
		});
	}
}