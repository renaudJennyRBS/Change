<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Http\Web;

use Change\Http\Web\Event;
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
	 * @param Event $event
	 */
	public function onAuthenticate(Event $event)
	{
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$session = new \Zend\Session\Container('Rbs_Commerce');
		if (!$user->authenticated())
		{
			$session['userId'] = null;
			return;
		}

		if (isset($session['userId']) && $session['userId'] == $user->getId())
		{
			return;
		}
		$session['userId'] = $user->getId();

		$profileManager = $event->getApplicationServices()->getProfileManager();;
		$profile = $profileManager->loadProfile($user, 'Rbs_Commerce');

		if ($profile instanceof \Rbs\Commerce\Std\Profile)
		{
			$session['profile'] = $profile;

			$saveProfile = false;
			$saveCart = false;

			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $commerceServices CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			if (!($commerceServices instanceof CommerceServices)) {
				$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
				return;
			}

			$context = $commerceServices->getContext();
			if ($context->getWebStore() && !$profile->getDefaultWebStoreId())
			{
				$profile->setDefaultWebStoreId($context->getWebStore()->getId());
				$saveProfile = true;
			}

			if ($context->getBillingArea() && !$profile->getDefaultBillingAreaId())
			{
				$profile->setDefaultBillingAreaId($context->getBillingArea()->getId());
				$profile->setDefaultZone($context->getZone());
				$saveProfile = true;
			}
			else if (!$context->getBillingArea() && $profile->getDefaultBillingAreaId())
			{
				$billingArea = $documentManager->getDocumentInstance($profile->getDefaultBillingAreaId());
				if ($billingArea instanceof \Rbs\Price\Tax\BillingAreaInterface)
				{
					$context->setBillingArea($billingArea);
				}
				else
				{
					$context->setBillingArea(null);
				}
				$context->setZone($profile->getDefaultZone());
			}

			$cartManager = $commerceServices->getCartManager();
			$currentCart = $context->getCartIdentifier() ? $cartManager->getCartByIdentifier($context->getCartIdentifier()) : null;
			if ($currentCart)
			{
				if ($currentCart->isProcessing())
				{
					$currentCart = null;
				}
				else if ($currentCart->isLocked())
				{
					$currentCart = $cartManager->getUnlockedCart($currentCart);
				}
			}

			if ($currentCart)
			{
				if ($profile->getLastCartIdentifier() && $profile->getLastCartIdentifier() !== $currentCart->getIdentifier())
				{
					$lastCart = $cartManager->getCartByIdentifier($profile->getLastCartIdentifier());
					if ($lastCart && !$lastCart->isProcessing())
					{
						$currentCart = $cartManager->mergeCart($currentCart, $lastCart);
						if (!$lastCart->isLocked())
						{
							$commerceServices->getStockManager()->cleanupReservations($lastCart->getIdentifier());
						}
						$saveCart = true;
					}
				}
			}
			elseif ($profile->getLastCartIdentifier())
			{
				$currentCart = $cartManager->getCartByIdentifier($profile->getLastCartIdentifier());
				if ($currentCart)
				{
					$saveCart = true;
					if ($currentCart->isProcessing())
					{
						$currentCart = null;
					}
					else if ($currentCart->isLocked())
					{
						$currentCart = $cartManager->getUnlockedCart($currentCart);
					}
				}
			}

			if ($currentCart)
			{
				$currentCartIdentifier = $currentCart->getIdentifier();
				if ($currentCart->getUserId() != $user->getId())
				{
					$currentCart->setUserId($user->getId());
					if (!$currentCart->getOwnerId())
					{
						$currentCart->setOwnerId($user->getId());
					}

					if (!$currentCart->getEmail())
					{
						$user = $documentManager->getDocumentInstance($user->getId());
						if ($user instanceof \Rbs\User\Documents\User)
						{
							$currentCart->setEmail($user->getEmail());
						}
					}

					$saveCart = true;
				}

				if ($saveCart)
				{
					$cartManager->normalize($currentCart);
					$cartManager->saveCart($currentCart);
				}
			}
			else
			{
				$currentCartIdentifier = null;
			}

			$context->setCartIdentifier($currentCartIdentifier);
			$context->save();

			if ($profile->getLastCartIdentifier() !== $currentCartIdentifier)
			{
				$profile->setLastCartIdentifier($currentCartIdentifier);
				$saveProfile = true;
			}

			if ($saveProfile)
			{
				if (!$profileManager)
				{
					$profileManager = $event->getApplicationServices()->getProfileManager();
				}
				$profileManager->saveProfile($user, $profile);
			}
		}
	}
}