<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\Http\Ajax;

use Change\Http\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\Http\Ajax\Listeners
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
		$events->attach(Event::EVENT_ACTION, [$this, 'registerActions']);
		$callback = function (Event $event)
		{
			(new \Rbs\User\Http\Ajax\Authentication())->authenticate($event);
		};
		$events->attach(Event::EVENT_AUTHENTICATE, $callback, 10);

		$callback = function (Event $event)
		{
			(new \Rbs\User\Http\Ajax\Authentication())->authenticateFromCookie($event);
		};
		$events->attach(Event::EVENT_AUTHENTICATE, $callback, 5);
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
		if ('Rbs/User/Login' === $actionPath)
		{
			if ($request->isPut())
			{
				$event->setAction(function (Event $event)
				{
					(new \Rbs\User\Http\Ajax\Authentication())->login($event);
				});
				$event->setAuthorization(function() use ($event) {return true;});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif ('Rbs/User/Logout' === $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\Authentication())->logout($event);
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
		elseif ('Rbs/User/Info' === $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\Authentication())->info($event);
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
		elseif ('Rbs/User/User/Profiles' === $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->getProfiles($event);
				});
				$event->setAuthorization(function() use ($event)
					{
						return $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->authenticated();
					}
				);
			}
			elseif ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->setProfiles($event);
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
		elseif ('Rbs/User/RevokeToken' === $actionPath)
		{
			if ($request->isDelete())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\Authentication())->revokeToken($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_DELETE]));
			}
			$event->setAuthorization(function() use ($event)
				{
					return $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->authenticated();
				}
			);
		}
		elseif ('Rbs/User/CheckEmailAvailability' === $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\Authentication())->checkEmailAvailability($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif ('Rbs/User/User/AccountRequest' === $actionPath)
		{
			if ($request->isPost())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->createAccountRequest($event);
				});
			}
			elseif ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->confirmAccountRequest($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_POST, \Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif ('Rbs/User/User/ResetPasswordRequest' === $actionPath)
		{
			if ($request->isPost())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->createResetPasswordRequest($event);
				});
			}
			elseif ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->confirmResetPasswordRequest($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_POST, \Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif ('Rbs/User/User/ChangePassword' === $actionPath)
		{
			if ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\User\Http\Ajax\User())->changePassword($event);
				});
				$event->setAuthorization(function() use ($event)
					{
						return $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser()->authenticated();
					}
				);
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_PUT]));
			}
		}
		elseif ('Rbs/Geo/Address/' === $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->getList($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			elseif ($request->isPost())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->addAddress($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(),
					[\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_POST]));
			}
		}
		elseif (preg_match('#^Rbs/Geo/Address/([0-9]+)$#', $actionPath, $matches))
		{
			$event->setParam('addressId', intval($matches[1]));
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->getAddress($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			elseif ($request->isPut())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->updateAddress($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			elseif ($request->isDelete())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->deleteAddress($event);
				});
				$event->setAuthorization(function() {return true;});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(),
					[\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_PUT, \Zend\Http\Request::METHOD_DELETE]));
			}
		}
		elseif ('Rbs/Geo/ValidateAddress' === $actionPath)
		{
			if ($request->isPost())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->validateAddress($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET, \Zend\Http\Request::METHOD_POST]));
			}
		}
		elseif ('Rbs/Geo/AddressFieldsCountries/' === $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->getAddressFieldsCountries($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif (preg_match('#^Rbs/Geo/AddressFields/([0-9]+)$#', $actionPath, $matches))
		{
			if ($request->isGet())
			{
				$event->setParam('addressFieldsId', intval($matches[1]));
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->getAddressFieldsData($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif ('Rbs/Geo/CityAutoCompletion/' == $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Address())->cityAutoCompletion($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
		elseif ('Rbs/Geo/Points/' == $actionPath)
		{
			if ($request->isGet())
			{
				$event->setAction(function (Event $event) {
					(new \Rbs\Geo\Http\Ajax\Points())->getList($event);
				});
			}
			else
			{
				$event->setResult($event->getController()->notAllowedError($request->getMethod(), [\Zend\Http\Request::METHOD_GET]));
			}
		}
	}
}