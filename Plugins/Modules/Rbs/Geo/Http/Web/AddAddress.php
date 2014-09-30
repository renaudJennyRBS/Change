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
 * @name \Rbs\Geo\Http\Web\AddAddress
 */
class AddAddress extends \Change\Http\Web\Actions\AbstractAjaxAction
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

		$request = $event->getRequest();
		$arguments = array_merge($request->getQuery()->toArray(), $request->getPost()->toArray());

		$fieldValues = isset($arguments['fieldValues']) ? $arguments['fieldValues'] : null;
		if (!is_array($fieldValues))
		{
			throw new \RuntimeException('Invalid field values', 999999);
		}
		$addressName = isset($arguments['name']) ? $arguments['name'] : null;

		$defaultFor = isset($arguments['defaultFor']) ? $arguments['defaultFor'] : null;
		$success = $genericServices->getGeoManager()->addAddress($fieldValues, $addressName, $defaultFor);
		if (!$success)
		{
			throw new \RuntimeException('Address creation failed', 999999);
		}

		(new GetAddresses())->execute($event);
	}
}