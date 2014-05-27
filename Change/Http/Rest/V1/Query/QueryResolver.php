<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Query;

use Change\Http\Rest\Request;
use Change\Http\Rest\V1\Resolver;

/**
 * @name \Change\Http\Rest\V1\Query\QueryResolver
 */
class QueryResolver implements \Change\Http\Rest\V1\NameSpaceDiscoverInterface
{
	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	function __construct(Resolver $resolver)
	{
		$this->resolver = $resolver;
	}

	/**
	 * @param \Change\Http\Event $event
	 * @param string[] $namespaceParts
	 * @return string[]
	 */
	public function getNextNamespace($event, $namespaceParts)
	{
		return array();
	}

	/**
	 * Set Event params: query
	 * @param \Change\Http\Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		$nbParts = count($resourceParts);
		if ($nbParts == 0 && $method === Request::METHOD_POST)
		{
			$action = function ($event)
			{
				$action = new DocumentQuery();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
	}
}