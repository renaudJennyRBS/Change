<?php
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

			if ($applicationServices && $user instanceof \Change\User\UserInterface)
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
		if ($user->authenticated())
		{
			$session = new \Zend\Session\Container('Rbs_Commerce');
			$pm = null;
			if (!isset($session['profile']))
			{
				$pm = $event->getApplicationServices()->getProfileManager();
				$session['profile'] = $pm->loadProfile($user, 'Rbs_Commerce');
			}

			$profile = $session['profile'];
			if ($profile instanceof \Rbs\Commerce\Std\Profile)
			{
				$saveProfile = false;

				/* @var $commerceServices CommerceServices */
				$commerceServices = $event->getServices('commerceServices');
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

				if ($context->getCartIdentifier())
				{
					if ($profile->getLastCartIdentifier() !== $context->getCartIdentifier())
					{
						if ($profile->getLastCartIdentifier())
						{
							$currentCart = $commerceServices->getCartManager()
								->getCartByIdentifier($context->getCartIdentifier());
							$lastCart = $commerceServices->getCartManager()
								->getCartByIdentifier($profile->getLastCartIdentifier());
							if ($lastCart && $currentCart)
							{
								$currentCart = $commerceServices->getCartManager()->mergeCart($currentCart, $lastCart);
								$commerceServices->getCartManager()->saveCart($currentCart);
								if ($context->getCartIdentifier() != $currentCart->getIdentifier())
								{
									$context->setCartIdentifier($currentCart->getIdentifier());
									$context->save();
								}
							}
						}
						$profile->setLastCartIdentifier($context->getCartIdentifier());
						$saveProfile = true;
					}
				}
				elseif ($profile->getLastCartIdentifier())
				{
					$context->setCartIdentifier($profile->getLastCartIdentifier());
					if ($profile->getDefaultBillingAreaId() && !$context->getBillingArea())
					{
						$billingArea = $event->getApplicationServices()->getDocumentManager()
							->getDocumentInstance($profile->getDefaultBillingAreaId());
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
					$context->save();
				}

				if ($saveProfile)
				{
					if (!$pm)
					{
						$pm = $event->getApplicationServices()->getProfileManager();
					}
					$pm->saveProfile($user, $profile);
				}
			}
		}
	}
}