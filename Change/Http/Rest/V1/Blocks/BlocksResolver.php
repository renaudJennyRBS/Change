<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Http\Rest\V1\Blocks;

use Change\Http\Event;
use Change\Http\Rest\Request;
use Change\Http\Rest\V1\DiscoverNameSpace;
use Change\Http\Rest\V1\Resolver;

/**
 * @name \Change\Http\Rest\V1\Blocks\BlocksResolver
 */
class BlocksResolver implements \Change\Http\Rest\V1\NameSpaceDiscoverInterface
{
	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\V1\Resolver $resolver
	 */
	public function __construct(Resolver $resolver)
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
		$namespaces = [];
		$bm = $event->getApplicationServices()->getBlockManager();
		if (!isset($namespaceParts[1]))
		{
			$vendors = array();
			foreach($bm->getBlockNames() as $name)
			{
				$a = explode('_', $name);
				$vendors[$a[0]] = true;
			}
			$names = array_keys($vendors);
		}
		elseif (!isset($namespaceParts[2]))
		{
			$vendor = $namespaceParts[1];
			$shortModulesNames = array();
			foreach($bm->getBlockNames() as $name)
			{
				$a = explode('_', $name);
				if ($a[0] === $vendor)
				{
					$shortModulesNames[$a[1]] = true;
				}
			}
			$names = array_keys($shortModulesNames);
		}
		else
		{
			return $namespaces;
		}

		$base = implode('.', $namespaceParts);
		foreach ($names as $name)
		{
			$namespaces[] = $base . '.' . $name;
		}
		return $namespaces;
	}

	/**
	 * Set event Params: modelName, documentId, LCID
	 * @param Event $event
	 * @param array $resourceParts
	 * @param $method
	 * @return void
	 */
	public function resolve($event, $resourceParts, $method)
	{
		if (count($resourceParts) < 2 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, 'blocks');
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
		elseif (count($resourceParts) == 2)
		{
			$event->setParam('vendor', $resourceParts[0]);
			$event->setParam('shortModuleName', $resourceParts[1]);
			$action = function ($event)
			{
				$action = new GetBlockCollection();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (count($resourceParts) == 3)
		{
			$event->setParam('blockName', implode('_', $resourceParts));
			$action = function ($event)
			{
				$action = new GetBlockInformation();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
	}
}