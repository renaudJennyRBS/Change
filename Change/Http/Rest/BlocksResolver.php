<?php
namespace Change\Http\Rest;

use Change\Http\Event;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Actions\GetBlockCollection;
use Change\Http\Rest\Actions\GetBlockInformation;
use Change\Presentation\PresentationServices;

/**
 * @name \Change\Http\Rest\BlocksResolver
 */
class BlocksResolver
{
	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
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
		$bm = $event->getPresentationServices()->getBlockManager();
		if (!isset($namespaceParts[1]))
		{
			$vendors = array();
			$names = $bm->getBlockNames();
			foreach($names as $name)
			{
				$a = explode('_', $name);
				$vendors[$a[0]] = true;
			}
			return array_keys($vendors);
		}
		elseif (!isset($namespaceParts[2]))
		{
			$vendor = $namespaceParts[1];
			$shortModulesNames = array();
			$names = $bm->getBlockNames();
			foreach($names as $name)
			{
				$a = explode('_', $name);
				if ($a[0] === $vendor)
				{
					$shortModulesNames[$a[1]] = true;
				}
			}
			return array_keys($shortModulesNames);
		}
		return array();
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
		if ($event->getPresentationServices() === null)
		{
			$event->setPresentationServices(new PresentationServices($event->getApplicationServices()));
		}
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