<?php
namespace Rbs\Timeline\Http\Rest;

use \Change\Http\Event;
use \Change\Http\Rest\Request;
use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Resolver;
use Rbs\Timeline\Http\Rest\Actions\GetIdentifiers;

class TimelineResolver {

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
		return array('userOrGroupIdentifiers');
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
			array_unshift($resourceParts, 'timeline');
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
			$actionName = $resourceParts[0];
			if ($actionName === 'userOrGroupIdentifiers')
			{
				$action = new GetIdentifiers();
				$event->setAction(function($event) use($action) {$action->execute($event);});
				/*
				$authorisation = function() use ($event)
				{
					return $event->getPermissionsManager()->isAllowed('Administrator');
				};
				$event->setAuthorization($authorisation);
				*/
			}
		}
	}
}