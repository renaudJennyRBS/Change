<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Http\Web;

/**
 * @name \Rbs\Geo\Http\Web\GetAddresses
 */
class GetAddresses extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		$genericServices = $event->getServices('genericServices');
		if (!($genericServices instanceof \Rbs\Generic\GenericServices))
		{
			throw new \RuntimeException('Unable to get GenericServices', 999999);
		}

		$geoManager = $genericServices->getGeoManager();
		$defaultAddress = $geoManager->getDefaultAddress();
		$defaultFieldValues = ($defaultAddress instanceof \Rbs\Geo\Address\AddressInterface) ? $defaultAddress->toArray() : null;
		$addresses = [];
		/* @var $address \Rbs\Geo\Address\AddressInterface */
		foreach ($geoManager->getAddresses() as $address)
		{
			$fieldValues = $address->toArray();
			$addressInfos = array(
				'fieldValues' => $address->toArray(),
				'lines' => $geoManager->getFormattedAddress($address)
			);
			if (method_exists($address, 'getName'))
			{
				$addressInfos['name'] = $address->getName();
			}

			if ($defaultFieldValues && $defaultFieldValues == $fieldValues)
			{
				$addressInfos['default'] = true;
				array_unshift($addresses, $addressInfos);
			}
			else
			{
				$addresses[] = $addressInfos;
			}
		}

		$result = $this->getNewAjaxResult($addresses);
		$event->setResult($result);
	}
}