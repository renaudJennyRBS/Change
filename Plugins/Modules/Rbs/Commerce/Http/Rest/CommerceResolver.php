<?php
namespace Rbs\Commerce\Http\Rest;

use Change\Http\Rest\Actions\DiscoverNameSpace;
use Change\Http\Rest\Resolver;
use Change\Http\Rest\Request;
use Rbs\Commerce\Http\Rest\Action\Cart;

/**
 * @name \Rbs\Commerce\Http\Rest\CommerceResolver
 */
class CommerceResolver
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
		return array('cart');
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
			array_unshift($resourceParts, 'commerce');
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
			if ($actionName === 'cart')
			{
				if ($method === Request::METHOD_POST)
				{
					$event->setAction(function($event) {(new Cart())->insertCart($event);});
					$event->setAuthorization(function() use ($event) {return $event->getAuthenticationManager()->getCurrentUser()->authenticated();});
				}
			}
		}
		elseif ($nbParts == 2 && $resourceParts[0] == 'cart')
		{
			$cartIdentifier = $resourceParts[1];
			$event->setParam('cartIdentifier', $cartIdentifier);
			if ($method === Request::METHOD_GET)
			{
				$event->setAction(function($event) {(new Cart())->getCart($event);});
				$event->setAuthorization(function() use ($event) {return $event->getAuthenticationManager()->getCurrentUser()->authenticated();});
			}
			elseif ($method === Request::METHOD_PUT)
			{
				$event->setAction(function($event) {(new Cart())->updateCart($event);});
				$event->setAuthorization(function() use ($event) {return $event->getAuthenticationManager()->getCurrentUser()->authenticated();});
			}
			elseif ($method === Request::METHOD_DELETE)
			{
				$event->setAction(function($event) {(new Cart())->deleteCart($event);});
				$event->setAuthorization(function() use ($event) {return $event->getAuthenticationManager()->getCurrentUser()->authenticated();});
			}
		}
	}
}