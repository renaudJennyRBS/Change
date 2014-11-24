<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Web;

use Rbs\Commerce\CommerceServices;

/**
 * @name \Rbs\Commerce\Http\Web\Loader
 */
class Loader
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function onRegisterServices(\Change\Http\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$applicationServices = $event->getApplicationServices();
			$extension = new \Rbs\Commerce\Presentation\TwigExtension($commerceServices);
			$applicationServices->getTemplateManager()->addExtension($extension);

			$context = $commerceServices->getContext();
			$context->getEventManager()->attach('load', array($this, 'onLoadContext'), 10);

			$context->getEventManager()->attach('save', array($this, 'onSaveContext'), 1);
		}
		else
		{
			$event->getApplicationServices()->getLogging()->error('Unable to register Http Web Commerce services');
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onLoadContext(\Change\Events\Event $event)
	{
		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$applicationServices = $event->getApplicationServices();
		$dm = $applicationServices->getDocumentManager();

		$session = new \Zend\Session\Container('Rbs_Commerce');
		if (isset($session['webStoreId']) && $session['webStoreId'])
		{
			$webStore = $dm->getDocumentInstance($session['webStoreId']);
			if (!($webStore instanceof \Rbs\Store\Documents\WebStore))
			{
				unset($session['webStoreId']);
			}
		}

		if (!isset($session['webStoreId']))
		{
			$session['webStoreId'] = false;
			$session['zone'] = null;
			$session['billingAreaId'] = 0;
			$session['parameters'] = [];
		}

		$context = $commerceServices->getContext();
		if ($session['webStoreId'])
		{
			/** @var \Rbs\Store\Documents\WebStore $webStore */
			$webStore = $dm->getDocumentInstance($session['webStoreId'], 'Rbs_Store_WebStore');
			if ($webStore)
			{
				$context->setWebStore($webStore);
			}
			else
			{
				unset($session['webStoreId']);
			}

			if ($session['billingAreaId'])
			{
				/** @var \Rbs\Price\Documents\BillingArea $billingArea */
				$billingArea = $dm->getDocumentInstance($session['billingAreaId'], 'Rbs_Price_BillingArea');
				if ($billingArea)
				{
					$context->setBillingArea($billingArea);
					$context->setZone($session['zone'] ? $session['zone'] : null);
				}
				else
				{
					unset($session['billingAreaId']);
					unset($session['zone']);
				}
			}
		}

		$context->getParameters()->fromArray(isset($session['parameters']) ? $session['parameters'] : []);
		$cartIdentifier = isset($session['cartIdentifier']) ? $session['cartIdentifier'] : null;
		$cart = $cartIdentifier ? $commerceServices->getCartManager()->getCartByIdentifier($cartIdentifier) : null;
		if ($cart && $cart->isProcessing())
		{
			$session['cartIdentifier'] = null;
			$cart = null;
		}
		$context->setCartIdentifier($cart ? $cart->getIdentifier() : null);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onSaveContext(\Change\Events\Event $event)
	{
		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$session = new \Zend\Session\Container('Rbs_Commerce');
		$context = $commerceServices->getContext();

		$session['parameters'] = $context->getParameters()->toArray();

		$webStore = $context->getWebStore();
		$session['webStoreId'] = ($webStore instanceof \Rbs\Store\Documents\WebStore) ? $webStore->getId() : false;
		$billingArea = $context->getBillingArea();
		$session['billingAreaId'] = ($billingArea instanceof \Rbs\Price\Documents\BillingArea) ? $billingArea->getId() : 0;
		$session['zone'] = $context->getZone();
		$session['cartIdentifier'] = $context->getCartIdentifier();
	}



	/**
	 * @param \Change\Events\Event $event
	 */
	public function onLoadProfile(\Change\Events\Event $event)
	{
		if ($event->getParam('profileName') === 'Rbs_Commerce')
		{
			$applicationServices = $event->getApplicationServices();
			$profile = new \Rbs\Commerce\Std\Profile();
			$user = $event->getParam('user');
			if ($user instanceof \Change\User\UserInterface)
			{
				$docUser = $applicationServices->getDocumentManager()->getDocumentInstance($user->getId());
				if ($docUser instanceof \Rbs\User\Documents\User)
				{
					$query = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Commerce_Profile');
					$query->andPredicates($query->eq('user', $docUser));
					$documentProfile = $query->getFirstDocument();
					if ($documentProfile instanceof \Rbs\Commerce\Documents\Profile)
					{
						$profile->setDefaultBillingAddressId($documentProfile->getDefaultBillingAddressId());
						$profile->setDefaultShippingAddressId($documentProfile->getDefaultShippingAddressId());
						$profile->setDefaultWebStoreId($documentProfile->getDefaultWebStoreId());
					}
				}
			}
			$event->setParam('profile', $profile);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onSaveProfile(\Change\Events\Event $event)
	{
		$profile = $event->getParam('profile');
		if ($profile instanceof \Rbs\Commerce\Std\Profile)
		{
			$user = $event->getParam('user');
			$applicationServices = $event->getApplicationServices();
			if ($user instanceof \Change\User\UserInterface)
			{
				$transactionManager = $applicationServices->getTransactionManager();
				try
				{
					$transactionManager->begin();
					$documentManager = $applicationServices->getDocumentManager();
					$docUser = $documentManager->getDocumentInstance($user->getId());
					if ($docUser instanceof \Rbs\User\Documents\User)
					{
						$query = $documentManager->getNewQuery('Rbs_Commerce_Profile');
						$query->andPredicates($query->eq('user', $docUser));

						/* @var $documentProfile \Rbs\Commerce\Documents\Profile */
						$documentProfile = $query->getFirstDocument();
						if ($documentProfile === null)
						{
							$documentProfile = $applicationServices->getDocumentManager()
								->getNewDocumentInstanceByModelName('Rbs_Commerce_Profile');
							$documentProfile->setUser($docUser);
						}

						$webStore = $documentManager->getDocumentInstance($profile->getDefaultWebStoreId());
						$documentProfile->setDefaultWebStore(($webStore instanceof \Rbs\Store\Documents\WebStore) ? $webStore : null);

						$address = $documentManager->getDocumentInstance($profile->getDefaultBillingAddressId());
						$documentProfile->setDefaultBillingAddress(($address instanceof \Rbs\Geo\Documents\Address) ? $address : null);

						$address = $documentManager->getDocumentInstance($profile->getDefaultShippingAddressId());
						$documentProfile->setDefaultShippingAddress(($address instanceof \Rbs\Geo\Documents\Address) ? $address : null);

						$documentProfile->save();
					}

					$transactionManager->commit();
				}
				catch (\Exception $e)
				{
					throw $transactionManager->rollBack($e);
				}
			}
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onProfiles(\Change\Events\Event $event)
	{
		$profiles = $event->getParam('profiles', []);
		$profiles = ['Rbs_Commerce'] + $profiles;
		$event->setParam('profiles', $profiles);
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onLogin(\Change\Events\Event $event)
	{
		$user = $event->getParam('user');
		if ($user instanceof \Change\User\UserInterface && $user->authenticated())
		{
			$session = new \Zend\Session\Container('Rbs_Commerce');
			$session['userId'] = $user->getId();

			$profileManager = $event->getApplicationServices()->getProfileManager();
			$profile = $profileManager->loadProfile($user, 'Rbs_Commerce');
			if (!($profile instanceof \Rbs\Commerce\Std\Profile))
			{
				$event->getApplicationServices()->getLogging()->error('Commerce profile not set in: ' . __METHOD__);
				return;
			}

			/* @var $commerceServices CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			if (!($commerceServices instanceof CommerceServices))
			{
				$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
				return;
			}

			$session['profile'] = $profile;
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$context = $commerceServices->getContext();
			$saveProfile = false;

			$cartManager = $commerceServices->getCartManager();
			$cartIdentifier = $context->getCartIdentifier();
			$currentCart = $cartIdentifier ? $cartManager->getCartByIdentifier($cartIdentifier) : null;

			$contextWebStore = $context->getWebStore();
			if (!$contextWebStore)
			{
				if ($profile->getDefaultWebStoreId())
				{
					$contextWebStore = $documentManager->getDocumentInstance($profile->getDefaultWebStoreId());
					if ($contextWebStore instanceof \Rbs\Store\Documents\WebStore)
					{
						$context->setWebStore($contextWebStore);
					}
				}
			}

			if ($contextWebStore instanceof \Rbs\Store\Documents\WebStore)
			{
				if ($contextWebStore->getId() !== $profile->getDefaultWebStoreId())
				{
					$profile->setDefaultWebStoreId($contextWebStore->getId());
					$saveProfile = true;
				}

				$lastCartIdentifier = $cartManager->getLastCartIdentifier($user, $contextWebStore);
				$lastCart = $lastCartIdentifier ? $cartManager->getCartByIdentifier($lastCartIdentifier) : null;
				if ($lastCart)
				{
					if (!$context->getBillingArea() && $lastCart->getBillingArea())
					{
						$context->setBillingArea($lastCart->getBillingArea());
						$context->setZone($lastCart->getZone());
					}

					$httpEvent = $this->getHttpEventOption($event);
					$request = $httpEvent ? $httpEvent->getRequest() : null;
					$ignoreProfileCart = false;
					if ($request instanceof \Change\Http\Ajax\Request)
					{
						$JSON = $request->getJSON();
						$ignoreProfileCart = is_array($JSON)
						&& isset($JSON['data']['ignoreProfileCart']) ? $JSON['data']['ignoreProfileCart'] : false;
					}

					if (!$ignoreProfileCart)
					{
						if ($currentCart)
						{
							if (!$currentCart->isLocked())
							{
								$currentCart = $cartManager->mergeCart($currentCart, $lastCart);
							}
						}
						else
						{
							$currentCart = $cartManager->getUnlockedCart($lastCart);
						}
					}
				}
			}

			if ($currentCart)
			{
				$cartIdentifier = $currentCart->getIdentifier();
				$currentCart->setUserId($user->getId());
				$documentUser = $documentManager->getDocumentInstance($user->getId());
				if ($documentUser instanceof \Rbs\User\Documents\User)
				{
					$currentCart->setEmail($documentUser->getEmail());
				}
				$cartManager->normalize($currentCart);
				$cartManager->saveCart($currentCart);
			}

			$context->setCartIdentifier($cartIdentifier);
			$context->save();

			if ($saveProfile)
			{
				$profileManager->saveProfile($user, $profile);
			}
		}
	}

	/**
	 * @param \Change\Http\Event $event
	 */
	public function onAuthenticate(\Change\Http\Event $event)
	{
		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof CommerceServices))
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
			return;
		}
		$context = $commerceServices->getContext();
		$context->load();
		$context->initializeContext(['website' => $event->getParam('website')]);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @return \Change\Http\Event|null
	 */
	protected function getHttpEventOption(\Change\Events\Event $event)
	{
		$options = $event->getParam('options');
		if (is_array($options) && isset($options['httpEvent']))
		{
			$httpEvent = $options['httpEvent'];
			if ($httpEvent instanceof \Change\Http\Event) {
				return $httpEvent;
			}
		}
		return null;
	}


	/**
	 * @param \Change\Events\Event $event
	 */
	public function onLogout(\Change\Events\Event $event)
	{
		$session = new \Zend\Session\Container('Rbs_Commerce');
		$session['userId'] = null;
		$session['profile'] = null;

		$httpEvent = $this->getHttpEventOption($event);

		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof CommerceServices))
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
			return;
		}

		$request = $httpEvent ? $httpEvent->getRequest() : null;
		if ($request instanceof \Change\Http\Ajax\Request)
		{
			$JSON = $request->getJSON();
			$keepCart = is_array($JSON) && isset($JSON['data']['keepCart']) ? $JSON['data']['keepCart'] : false;
		}
		else
		{
			$keepCart = false;
		}

		if (!$keepCart)
		{
			$context = $commerceServices->getContext();
			if ($context->getCartIdentifier())
			{
				$context->setCartIdentifier(null);
				$context->save();
			}
		}
		else
		{
			$cartManager = $commerceServices->getCartManager();

			$context = $commerceServices->getContext();
			$contextCartIdentifier = $context->getCartIdentifier();
			$currentCart = $contextCartIdentifier ? $cartManager->getCartByIdentifier($contextCartIdentifier) : null;

			$user = new \Change\User\AnonymousUser();
			$newCart = $cartManager->cloneCartContentForUser($currentCart, $user);
			if ($newCart)
			{
				$newCart->setEmail(null);
				$newCart->getContext()->set('userName', null);
				$context->setCartIdentifier($newCart->getIdentifier());
				$context->save();
				$cartManager->normalize($newCart);
				$cartManager->saveCart($newCart);
			}
		}
	}
}