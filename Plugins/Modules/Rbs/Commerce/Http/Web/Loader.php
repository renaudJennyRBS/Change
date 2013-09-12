<?php
namespace Rbs\Commerce\Http\Web;

use Change\Documents\Query\Query;
use Change\Http\Web\Event;
use Rbs\Commerce\Services\CommerceServices;

/**
 * @name \Rbs\Commerce\Http\Web\Loader
 */
class Loader
{
	/**
	 * @param \Change\Http\Web\Event $event
	 */
	public function onRequest(Event $event)
	{
		$documentServices = $event->getDocumentServices();
		$commerceServices = new CommerceServices($event->getApplicationServices(), $documentServices);
		$event->setParam('commerceServices', $commerceServices);
		$extension = new \Rbs\Commerce\Presentation\TwigExtension($commerceServices);
		$event->getPresentationServices()->getTemplateManager()->addExtension($extension);

		$commerceServices->getEventManager()->attach('load', array($this, 'onLoadCommerceServices'), 5);
		$commerceServices->getEventManager()->attach('save',array($this, 'onSaveCommerceServices'), 5);
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onLoadCommerceServices(\Zend\EventManager\Event $event)
	{
		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getParam('commerceServices');
		$documentServices = $commerceServices->getDocumentServices();
		$session = new \Zend\Session\Container('Rbs_Commerce');

		if (isset($session['billingAreaId']) && $session['billingAreaId'])
		{
			$billingArea = $documentServices->getDocumentManager()->getDocumentInstance($session['billingAreaId']);
			if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
			{
				$commerceServices->setBillingArea($billingArea);
			}
			else
			{
				unset($session['billingAreaId']);
			}
		}

		if (!isset($session['billingAreaId']))
		{
			$session['zone'] = null;
			$session['billingAreaId'] = 0;

			$query = new Query($documentServices, 'Rbs_Price_BillingArea');
			$billingArea = $query->getFirstDocument();
			if ($billingArea instanceof \Rbs\Price\Documents\BillingArea)
			{
				$session['billingAreaId'] = $billingArea->getId();
				if ($billingArea->getTaxes()->count())
				{
					$tax = $billingArea->getTaxes()[0];
					$session['zone'] = $tax->getDefaultZone();
				}
			}
		}
		$commerceServices->setZone(isset($session['zone']) ? $session['zone'] : null);
		$commerceServices->setCartIdentifier(isset($session['cartIdentifier']) ? $session['cartIdentifier']: null);
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onSaveCommerceServices(\Zend\EventManager\Event $event)
	{
		/* @var $commerceServices CommerceServices */
		$commerceServices = $event->getParam('commerceServices');
		$session = new \Zend\Session\Container('Rbs_Commerce');
		$session['cartIdentifier'] = $commerceServices->getCartIdentifier();
		$billingArea = $commerceServices->getBillingArea();
		$session['billingAreaId'] = ($billingArea instanceof \Rbs\Price\Documents\BillingArea) ? $billingArea->getId() : false;
		$session['zone'] = $commerceServices->getZone();
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 */
	public function onLoadProfile(\Zend\EventManager\Event $event)
	{
		if ($event->getParam('profileName') === 'Rbs_Commerce')
		{
			$documentServices = $event->getParam('documentServices');
			$profile = new \Rbs\Commerce\Std\Profile();
			$user = $event->getParam('user');

			if ($documentServices instanceof \Change\Documents\DocumentServices && $user instanceof \Change\User\UserInterface)
			{
				$docUser = $documentServices->getDocumentManager()->getDocumentInstance($user->getId());
				if ($docUser instanceof \Rbs\User\Documents\User)
				{
					$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Commerce_Profile');
					$query->andPredicates($query->eq('user', $docUser));
					$documentProfile = $query->getFirstDocument();
					if ($documentProfile instanceof \Rbs\Commerce\Documents\Profile)
					{
						$profile->setLastCartIdentifier($documentProfile->getLastCartIdentifier());
						$profile->setDefaultAddressId($documentProfile->getDefaultAddressId());
						$profile->setDefaultBillingAreaId($documentProfile->getDefaultBillingAreaId());
						$profile->setDefaultZone($documentProfile->getDefaultZone());
					}
				}
			}
			$event->setParam('profile', $profile);
		}
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @throws \Exception
	 */
	public function onSaveProfile(\Zend\EventManager\Event $event)
	{
		$profile = $event->getParam('profile');
		if ($profile instanceof \Rbs\Commerce\Std\Profile)
		{
			$user = $event->getParam('user');
			/* @var $documentServices \Change\Documents\DocumentServices */
			$documentServices = $event->getParam('documentServices');
			if ($user instanceof \Change\User\UserInterface)
			{
				$transactionManager = $documentServices->getApplicationServices()->getTransactionManager();
				try
				{
					$transactionManager->begin();
					$documentManager = $documentServices->getDocumentManager();
					$docUser = $documentManager->getDocumentInstance($user->getId());
					if ($docUser instanceof \Rbs\User\Documents\User)
					{
						$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Commerce_Profile');
						$query->andPredicates($query->eq('user', $docUser));

						/* @var $documentProfile \Rbs\Commerce\Documents\Profile */
						$documentProfile = $query->getFirstDocument();
						if ($documentProfile === null)
						{
							$documentProfile = $documentServices->getDocumentManager()
								->getNewDocumentInstanceByModelName('Rbs_Commerce_Profile');
							$documentProfile->setUser($docUser);
						}

						$documentProfile->setDefaultZone($profile->getDefaultZone());
						$documentProfile->setLastCartIdentifier($profile->getLastCartIdentifier());
						$billingArea = $documentManager->getDocumentInstance($profile->getDefaultBillingAreaId());
						$documentProfile->setDefaultBillingArea(($billingArea instanceof \Rbs\Price\Documents\BillingArea) ? $billingArea : null);

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
	 * @param \Zend\EventManager\Event $event
	 */
	public function onProfiles(\Zend\EventManager\Event $event)
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
				$pm = new \Change\User\ProfileManager();
				$pm->setDocumentServices($event->getDocumentServices());
				$session['profile'] = $pm->loadProfile($user, 'Rbs_Commerce');

			}

			$profile = $session['profile'];
			if ($profile instanceof \Rbs\Commerce\Std\Profile)
			{
				$saveProfile = false;

				/* @var $commerceServices CommerceServices */
				$commerceServices = $event->getParam('commerceServices');
				if ($commerceServices->getBillingArea() && !$profile->getDefaultBillingAreaId())
				{
					$profile->setDefaultBillingAreaId($commerceServices->getBillingArea()->getId());
					$profile->setDefaultZone($commerceServices->getZone());
					$saveProfile = true;
				}

				if ($commerceServices->getCartIdentifier())
				{
					if ($profile->getLastCartIdentifier() !== $commerceServices->getCartIdentifier())
					{
						if ($profile->getLastCartIdentifier())
						{
							$currentCart = $commerceServices->getCartManager()->getCartByIdentifier($commerceServices->getCartIdentifier());
							$lastCart = $commerceServices->getCartManager()->getCartByIdentifier($profile->getLastCartIdentifier());
							if ($lastCart && $currentCart)
							{
								$currentCart = $commerceServices->getCartManager()->mergeCart($currentCart, $lastCart);
								$commerceServices->getCartManager()->saveCart($currentCart);
								if ($commerceServices->getCartIdentifier() != $currentCart->getIdentifier())
								{
									$commerceServices->setCartIdentifier($currentCart->getIdentifier());
									$commerceServices->save();
								}
							}
						}
						$profile->setLastCartIdentifier($commerceServices->getCartIdentifier());
						$saveProfile = true;
					}
				}
				elseif ($profile->getLastCartIdentifier())
				{
					$commerceServices->setCartIdentifier($profile->getLastCartIdentifier());
					if ($profile->getDefaultBillingAreaId() && !$commerceServices->getBillingArea())
					{
						$commerceServices->setBillingArea($event->getDocumentServices()->getDocumentManager()->getDocumentInstance($profile->getDefaultBillingAreaId()));
						$commerceServices->setZone($profile->getDefaultZone());
					}
					$commerceServices->save();
				}

				if ($saveProfile)
				{
					if (!$pm)
					{
						$pm = new \Change\User\ProfileManager();
						$pm->setDocumentServices($event->getDocumentServices());
					}
					$pm->saveProfile($user, $profile);
				}
			}
		}
	}
}