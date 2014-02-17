<?php
namespace Change\Http\Rest;

use Change\Http\Event;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Actions\GetModelCollection;
use Change\Http\Rest\Actions\GetModelInformation;

/**
 * @name \Change\Http\Rest\ModelsResolver
 */
class ModelsResolver
{
	/**
	 * @param \Change\Http\Rest\Resolver $resolver
	 */
	protected $resolver;

	/**
	 * @param \Change\Http\Rest\Resolver $resolver
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
		if (!isset($namespaceParts[1]))
		{
			return $event->getApplicationServices()->getModelManager()->getVendors();
		}
		elseif (!isset($namespaceParts[2]))
		{
			$vendor = $namespaceParts[1];
			return $event->getApplicationServices()->getModelManager()->getShortModulesNames($vendor);
		}
		elseif (!isset($namespaceParts[3]))
		{
			return $event->getApplicationServices()->getModelManager()
				->getShortDocumentsNames($namespaceParts[1], $namespaceParts[2]);
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
		if (count($resourceParts) < 2 && $method === Request::METHOD_GET)
		{
			array_unshift($resourceParts, 'models');
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
				$action = new GetModelCollection();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
		elseif (count($resourceParts) == 3)
		{
			$event->setParam('modelName', implode('_', $resourceParts));
			$action = function ($event)
			{
				$action = new GetModelInformation();
				$action->execute($event);
			};
			$event->setAction($action);
			return;
		}
	}
}