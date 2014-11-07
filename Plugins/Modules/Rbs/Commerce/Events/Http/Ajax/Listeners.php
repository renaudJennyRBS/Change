<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\Http\Ajax;

use Change\Http\Event;
use Rbs\Commerce\Http\Web\Loader;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\Http\Ajax\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$events->attach(Event::EVENT_ACTION, array($this, 'registerActions'));
		$events->attach(Event::EVENT_REQUEST, function ($event) { (new Loader)->onRegisterServices($event); }, 1);
		$events->attach(Event::EVENT_AUTHENTICATE, function ($event) { (new Loader())->onAuthenticate($event);}, 1);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}

	/**
	 * @param Event $event
	 */
	public function registerActions(Event $event)
	{
		$actionPath = $event->getParam('actionPath');
		$request = $event->getRequest();
		if (preg_match('#^Rbs/Catalog/Product/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setParam('productId', intval($matches[1]));
				$event->setAction(function (Event $event) {
					(new \Rbs\Catalog\Http\Ajax\Product())->getData($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Catalog/Product/$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Catalog\Http\Ajax\Product())->getListData($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/Cart$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Cart())->getCart($event);
				});
			}
			if ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Cart())->updateCart($event);
				});

				// Initialize Authentication Manager.
				$event->setAuthorization(function() {return true;});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/Cart/ShippingFeesEvaluation$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Cart())->getShippingFeesEvaluation($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/Cart/Transaction$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Cart())->getCartTransaction($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Order/Order/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$order = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance(intval($matches[1]), 'Rbs_Order_Order');
				if ($order instanceof \Rbs\Order\Documents\Order)
				{
					$event->setParam('orderId', $order->getId());
					$event->setAction(function (Event $event) {
						(new \Rbs\Order\Http\Ajax\Order())->getOrder($event);
					});
					$event->setAuthorization(function () use ($event, $order) {
						/** @var \Rbs\Commerce\CommerceServices $commerceServices */
						$commerceServices = $event->getServices('commerceServices');
						if (!$commerceServices)
						{
							return false;
						}
						$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
						return $commerceServices->getOrderManager()->canViewOrder(['userId' => $currentUser->getId(), 'order' => $order]);
					});
				}
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif (preg_match('#^Rbs/Order/Order/$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Order\Http\Ajax\Order())->getOrderList($event);
				});
				$event->setAuthorization(function() use ($event)
					{
						return $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->authenticated();
					}
				);
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/Context$#', $actionPath, $matches))
		{
			if ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Context())->set($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/ContextConfiguration$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Context())->getConfiguration($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/Process/([0-9]+)/ShippingModesByAddress/$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setParam('processId', intval($matches[1]));
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Process())->getShippingModesByAddress($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Commerce/Process/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setParam('processId', intval($matches[1]));
				$event->setAction(function (Event $event) {
					(new \Rbs\Commerce\Http\Ajax\Process())->getData($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Productreturn/Process/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$process = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance(intval($matches[1]), 'Rbs_Productreturn_Process');
				if ($process instanceof \Rbs\Productreturn\Documents\Process)
				{
					$event->setParam('processId', $process->getId());
					$event->setAction(function (Event $event) {
						(new \Rbs\Productreturn\Http\Ajax\Process())->getOrder($event);
					});
				}
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Productreturn/ProductReturn/$#', $actionPath, $matches))
		{
			if ($request->isPost())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Productreturn\Http\Ajax\ProductReturn())->submitReturnRequest($event);
				});
				$event->setAuthorization(function() use ($event) {
					return $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->authenticated();
				});
			}
			elseif ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Productreturn\Http\Ajax\ProductReturn())->getListData($event);
				});
				$event->setAuthorization(function() use ($event) {
					return $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->authenticated();
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_POST]));
			}
		}
		elseif (preg_match('#^Rbs/Productreturn/ProductReturn/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$return = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance(intval($matches[1]), 'Rbs_Productreturn_ProductReturn');
				if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
				{
					$event->setParam('returnId', $return->getId());
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Productreturn\Http\Ajax\ProductReturn())->getData($event);
					});
					$event->setAuthorization(function () use ($event, $return) {
						/** @var \Rbs\Commerce\CommerceServices $commerceServices */
						$commerceServices = $event->getServices('commerceServices');
						if (!$commerceServices)
						{
							return false;
						}
						$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
						return $commerceServices->getReturnManager()->canViewReturn(['userId' => $currentUser->getId(), 'return' => $return]);
					});
				}
			}
			elseif ($request->isPut())
			{
				$return = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance(intval($matches[1]), 'Rbs_Productreturn_ProductReturn');
				if ($return instanceof \Rbs\Productreturn\Documents\ProductReturn)
				{
					$event->setParam('returnId', $return->getId());
					$event->setAction(function (Event $event)
					{
						(new \Rbs\Productreturn\Http\Ajax\ProductReturn())->updateReturn($event);
					});
					$event->setAuthorization(function () use ($event, $return) {
						/** @var \Rbs\Commerce\CommerceServices $commerceServices */
						$commerceServices = $event->getServices('commerceServices');
						if (!$commerceServices)
						{
							return false;
						}
						$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
						return $commerceServices->getReturnManager()->canViewReturn(['userId' => $currentUser->getId(), 'return' => $return]);
					});
				}
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_PUT]));
			}
		}
	}
}