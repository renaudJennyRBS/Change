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
	 * @param \Change\Events\Event $event
	 */
	public function onRegisterServices(\Change\Events\Event $event)
	{
		$commerceServices = $event->getServices('commerceServices');
		if ($commerceServices instanceof CommerceServices)
		{
			$applicationServices = $event->getApplicationServices();
			$extension = new \Rbs\Commerce\Presentation\TwigExtension($commerceServices);
			$applicationServices->getTemplateManager()->addExtension($extension);

			$commerceServices->getContext()->getEventManager()->attach('load', array($this, 'onLoadContext'), 5);
			$commerceServices->getContext()->getEventManager()->attach('save', array($this, 'onSaveContext'), 5);
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

			$query = $dm->getNewQuery('Rbs_Store_WebStore');
			$webStores = $query->getDocuments(0, 2);
			if ($webStores->count() == 1)
			{
				/* @var $webStore \Rbs\Store\Documents\WebStore */
				$webStore = $webStores[0];
				$session['webStoreId'] = $webStore->getId();
				if ($webStore->getBillingAreasCount() == 1)
				{
					$billingArea = $webStore->getBillingAreas()[0];
					if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
					{
						$session['billingAreaId'] = $billingArea->getId();
						$zones = array();
						foreach ($billingArea->getTaxes() as $tax)
						{
							$zones = array_merge($zones, $tax->getZoneCodes());
						}
						$zones = array_unique($zones);
						if (count($zones) == 1)
						{
							$session['zone'] = $zones[0];
						}
					}
				}
			}
		}
		$context = $commerceServices->getContext();
		if ($session['webStoreId'])
		{
			$context->setWebStore($dm->getDocumentInstance($session['webStoreId']));
		}
		if ($session['billingAreaId'])
		{
			$context->setBillingArea($dm->getDocumentInstance($session['billingAreaId']));
		}
		$context->setZone($session['zone']);

		$context->setCartIdentifier(isset($session['cartIdentifier']) ? $session['cartIdentifier'] : null);
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

		$webStore = $context->getWebStore();
		$session['webStoreId'] = ($webStore instanceof \Rbs\Store\Documents\WebStore) ? $webStore->getId() : false;

		$billingArea = $context->getBillingArea();
		$session['billingAreaId'] = ($billingArea instanceof \Rbs\Price\Documents\BillingArea) ? $billingArea->getId() : 0;
		$session['zone'] = $context->getZone();
		$session['cartIdentifier'] = $context->getCartIdentifier();

		// If the cart is replaced, update the last cart identifier in the profile.
		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if ($user->authenticated())
		{
			if (isset($session['profile']) && $session['profile'] instanceof \Rbs\Commerce\Std\Profile)
			{
				/* @var \Rbs\Commerce\Std\Profile $profile */
				$profile = $session['profile'];
				if ($profile->getLastCartIdentifier() !== $context->getCartIdentifier())
				{
					$profile->setLastCartIdentifier($context->getCartIdentifier());
					$pm = $event->getApplicationServices()->getProfileManager();
					$pm->saveProfile($user, $profile);
				}
			}
		}
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
						$profile->setLastCartIdentifier($documentProfile->getLastCartIdentifier());
						$profile->setDefaultAddressId($documentProfile->getDefaultAddressId());
						$profile->setDefaultWebStoreId($documentProfile->getDefaultWebStoreId());
						$profile->setDefaultBillingAreaId($documentProfile->getDefaultBillingAreaId());
						$profile->setDefaultZone($documentProfile->getDefaultZone());
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

						$documentProfile->setDefaultZone($profile->getDefaultZone());
						$documentProfile->setLastCartIdentifier($profile->getLastCartIdentifier());

						$webStore = $documentManager->getDocumentInstance($profile->getDefaultWebStoreId());
						$documentProfile->setDefaultWebStore(($webStore instanceof
							\Rbs\Store\Documents\WebStore) ? $webStore : null);

						$billingArea = $documentManager->getDocumentInstance($profile->getDefaultBillingAreaId());
						$documentProfile->setDefaultBillingArea(($billingArea instanceof
							\Rbs\Price\Documents\BillingArea) ? $billingArea : null);

						$address = $documentManager->getDocumentInstance($profile->getDefaultAddressId());
						$documentProfile->setDefaultAddress(($address instanceof \Rbs\Geo\Documents\Address) ? $address : null);

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
			if ($context->getWebStore() && !$profile->getDefaultWebStoreId())
			{
				$profile->setDefaultWebStoreId($context->getWebStore()->getId());
			}
			elseif (!$context->getWebStore() && $profile->getDefaultWebStoreId())
			{
				$webStore = $documentManager->getDocumentInstance($profile->getDefaultWebStoreId());
				if ($webStore instanceof \Rbs\Store\Documents\WebStore)
				{
					$context->setWebStore($webStore);
				}
			}

			if ($context->getBillingArea() && !$profile->getDefaultBillingAreaId())
			{
				$profile->setDefaultBillingAreaId($context->getBillingArea()->getId());
				$profile->setDefaultZone($context->getZone());
			}
			elseif (!$context->getBillingArea() && $profile->getDefaultBillingAreaId())
			{
				$billingArea = $documentManager->getDocumentInstance($profile->getDefaultBillingAreaId());
				if ($billingArea instanceof \Rbs\Price\Tax\BillingAreaInterface)
				{
					$context->setBillingArea($billingArea);
					$context->setZone($profile->getDefaultZone());
				}
			}

			$cartManager = $commerceServices->getCartManager();
			$contextCartIdentifier = $context->getCartIdentifier();
			$profileCartIdentifier = $profile->getLastCartIdentifier();

			$currentCart = $contextCartIdentifier ? $cartManager->getCartByIdentifier($contextCartIdentifier) : null;
			$profileCart = $profileCartIdentifier && ($profileCartIdentifier != $contextCartIdentifier) ?
				$cartManager->getCartByIdentifier($profileCartIdentifier) : null;


			if ($currentCart)
			{
				if (!$currentCart->isLocked() && $profileCart)
				{
					$currentCart = $cartManager->mergeCart($currentCart, $profileCart);
				}
			}
			else
			{
				if ($profileCart)
				{
					$currentCart = $cartManager->getUnlockedCart($profileCart);
				}
			}

			$cartIdentifier = null;
			if ($currentCart)
			{
				$cartIdentifier = $currentCart->getIdentifier();
				$currentCart->setUserId($user->getId());
				if (!$currentCart->getOwnerId())
				{
					$currentCart->setOwnerId($user->getId());
				}

				if (!$currentCart->getEmail())
				{
					$documentUser = $documentManager->getDocumentInstance($user->getId());
					if ($documentUser instanceof \Rbs\User\Documents\User)
					{
						$currentCart->setEmail($documentUser->getEmail());
					}
				}

				$cartManager->normalize($currentCart);
				$cartManager->saveCart($currentCart);
			}

			$context->setCartIdentifier($cartIdentifier);
			$context->save();

			$profile->setLastCartIdentifier($cartIdentifier);
			$profileManager->saveProfile($user, $profile);
		}
	}
	/**
	 * @param \Change\Http\Web\Event $event
	 */
	public function onAuthenticate(\Change\Http\Web\Event $event)
	{
		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof CommerceServices))
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
			return;
		}

		$cartManager = $commerceServices->getCartManager();
		$context = $commerceServices->getContext();
		$contextCartIdentifier = $context->getCartIdentifier();
		$currentCart = $contextCartIdentifier ? $cartManager->getCartByIdentifier($contextCartIdentifier) : null;
		if ($currentCart && $currentCart->isProcessing())
		{
			$context->setCartIdentifier(null);
			$context->save();
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 */
	public function onLogout(\Change\Events\Event $event)
	{
		$session = new \Zend\Session\Container('Rbs_Commerce');
		$session['userId'] = null;
		$session['profile'] = null;

		$options = $event->getParam('options');
		/** @var $httpEvent \Change\Http\Web\Event */
		$httpEvent = $options['httpEvent'];

		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		if (!($commerceServices instanceof CommerceServices))
		{
			$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
			return;
		}

		$keepCart = $httpEvent->getRequest()->getPost('keepCart', false);
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
				$context->setCartIdentifier($newCart->getIdentifier());
				$context->save();

				if ($newCart->getUserId() != 0)
				{
					$documentUser = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($user->getId());
					if ($documentUser instanceof \Rbs\User\Documents\User)
					{
						$newCart->setEmail($documentUser->getEmail());
					}
				}

				$cartManager->normalize($newCart);
				$cartManager->saveCart($newCart);
			}
		}
	}
}