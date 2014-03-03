<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Website\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;

/**
 * @name \Rbs\Website\Blocks\SiteMap
 */
class SiteMap extends Menu
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getApplication, getApplicationServices, getServices, getHttpRequest
	 * Event params includes all params from Http\Event (ex: pathRule and page).
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->getParameterMeta('templateName')->setDefaultValue('siteMap.twig');
		$parameters->getParameterMeta('maxLevel')->setDefaultValue(5);
		$parameters->setParameterValue(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME, $event->getParam('website')->getId());
		return $parameters;
	}
}