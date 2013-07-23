<?php
namespace Rbs\User\Http\Rest;

use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Resolver;
use Change\Http\Rest\Request;
use Rbs\User\Http\Rest\Actions\GetUserTokens;
use Rbs\User\Http\Rest\Actions\RevokeToken;

/**
 * @name \Rbs\User\Http\Rest\UserResolver
 */
class UserResolver
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
		return array('userTokens', 'revokeToken');
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
			array_unshift($resourceParts, 'user');
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
			if ($actionName === 'userTokens')
			{
				$action = new GetUserTokens();
				$event->setAction(function($event) use($action) {$action->execute($event);});
				$authorisation = function() use ($event)
				{
					return $event->getPermissionsManager()->isAllowed('Consumer', $event->getAuthenticationManager()->getCurrentUser()->getId());
				};
				$event->setAuthorization($authorisation);
			}
			else if ($actionName === 'revokeToken')
			{
				$action = new RevokeToken();
				$event->setAction(function($event) use($action) {$action->execute($event);});
				$authorisation = function() use ($event)
				{
					return $event->getPermissionsManager()->isAllowed('Administrator', $event->getAuthenticationManager()->getCurrentUser()->getId());
				};
				$event->setAuthorization($authorisation);
			}
		}
	}
}