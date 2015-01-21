<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Geo\Presentation;

/**
* @name \Rbs\Geo\Presentation\ThemeManagerEvents
*/
class ThemeManagerEvents
{
	public function onAddPageResources(\Change\Events\Event $event)
	{
		/** @var \Change\Http\Web\Result\Page $result */
		$result = $event->getParam('pageResult');
		if ($result instanceof \Change\Http\Web\Result\Page)
		{
			$rbsGeoConfig = $event->getApplication()->getConfiguration('Rbs/Geo/Google');
			if (is_array($rbsGeoConfig) && count($rbsGeoConfig))
			{
				$result->setJsonObject('Rbs_Geo_Config', ['Google' => $rbsGeoConfig]);
			}
		}
	}
}