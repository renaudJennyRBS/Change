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
 * @name \Rbs\Geo\Http\Web\SetDefaultAddress
 */
class SetDefaultAddress extends \Change\Http\Web\Actions\AbstractAjaxAction
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

		if (!isset($arguments['id']))
		{
			throw new \RuntimeException('No address id specified', 999999);
		}

		$defaultFor = isset($arguments['defaultFor']) ? $arguments['defaultFor'] : 'default';
		$success = $genericServices->getGeoManager()->setDefaultAddress($arguments['id'], $defaultFor);
		if (!$success)
		{
			throw new \RuntimeException('Setting default address failed', 999999);
		}
		(new GetAddresses())->execute($event);
	}
}