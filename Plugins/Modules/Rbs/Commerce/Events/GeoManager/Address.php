<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\GeoManager;

/**
 * @name \Rbs\Commerce\Events\GeoManager\Address
 */
class Address
{
	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onDefaultForNames($event)
	{
		$names = $event->getParam('names');
		if (is_array($names))
		{
			$names += ['billing', 'shipping'];
			$event->setParam('names', $names);
		}
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onSetDefaultAddress($event)
	{
		$defaultFor = $event->getParam('defaultFor');
		if (!is_array($defaultFor) || (!in_array('billing', $defaultFor) && !in_array('shipping', $defaultFor)))
		{
			return;
		}

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$address = $event->getParam('address');
		if (is_numeric($address))
		{
			$address = $documentManager->getDocumentInstance(intval($address));
		}
		if (!($address instanceof \Rbs\Geo\Documents\Address))
		{
			return;
		}

		$user = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		$profileManager = $event->getApplicationServices()->getProfileManager();
		$profile = $profileManager->loadProfile($user, 'Rbs_Commerce');
		if (!($profile instanceof \Rbs\Commerce\Std\Profile))
		{
			return;
		}

		// If the addressId represents an address document and there is commerce profile, set the default address id.
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			if (in_array('billing', $defaultFor))
			{
				$profile->setDefaultBillingAddressId($address->getId());
			}
			if (in_array('shipping', $defaultFor))
			{
				$profile->setDefaultShippingAddressId($address->getId());
			}
			$profileManager->saveProfile($user, $profile);

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		$event->setParam('done', true);
	}

	/**
	 * @param \Change\Events\Event $event
	 * @throws \Exception
	 */
	public function onGetDefaultAddress($event)
	{
		if ($event->getParam('defaultAddress') instanceof \Rbs\Geo\Address\AddressInterface)
		{
			return;
		}

		$defaultFor = $event->getParam('defaultFor');
		if (!is_array($defaultFor) || (!in_array('billing', $defaultFor) && !in_array('shipping', $defaultFor)))
		{
			return;
		}
		$applicationServices = $event->getApplicationServices();

		$user = $applicationServices->getAuthenticationManager()->getCurrentUser();
		if (!$user->authenticated())
		{
			return;
		}

		$profileManager = $applicationServices->getProfileManager();
		$profile = $profileManager->loadProfile($user, 'Rbs_Commerce');
		if (!($profile instanceof \Rbs\Commerce\Std\Profile))
		{
			return;
		}

		foreach ($defaultFor as $for)
		{
			if ($for === 'billing')
			{
				$address = $applicationServices->getDocumentManager()
					->getDocumentInstance($profile->getDefaultBillingAddressId());
				if ($address instanceof \Rbs\Geo\Documents\Address)
				{
					$event->setParam('defaultAddress', $address);
					break;
				}
			}
			elseif ($for === 'shipping')
			{
				$address = $applicationServices->getDocumentManager()
					->getDocumentInstance($profile->getDefaultShippingAddressId());
				if ($address instanceof \Rbs\Geo\Documents\Address)
				{
					$event->setParam('defaultAddress', $address);
					break;
				}
			}
		}
	}
}