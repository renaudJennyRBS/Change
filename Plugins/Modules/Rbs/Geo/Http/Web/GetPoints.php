<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Http\Web;

/**
 * @name \Rbs\Geo\Http\Web\GetPoints
 */
class GetPoints extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \RuntimeException
	 * @throws \Exception
	 * @return mixed
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			$genericServices = $event->getServices('genericServices');
			if (!($genericServices instanceof \Rbs\Generic\GenericServices))
			{
				throw new \RuntimeException('Unable to get GenericServices', 999999);
			}

			$params = $event->getRequest()->getPost()->toArray();
			$geoManager = $genericServices->getGeoManager();

			$cities = $geoManager->getPoints($params);

			$result = $this->getNewAjaxResult($cities);
			$event->setResult($result);
		}
	}
}