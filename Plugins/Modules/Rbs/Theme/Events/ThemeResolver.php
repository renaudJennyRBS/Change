<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Theme\Events;

/**
 * @name \Rbs\Theme\Events\ThemeResolver
 */
class ThemeResolver
{
	/**
	 * @param \Change\Events\Event $event
	 * @return \Rbs\Theme\Documents\Theme|null
	 */
	public function resolve($event)
	{
		$themeName = $event->getParam('themeName');
		$applicationServices = $event->getApplicationServices();
		if ($themeName && $applicationServices)
		{
			$themeModel = $applicationServices->getModelManager()->getModelByName('Rbs_Theme_Theme');
			$query = $applicationServices->getDocumentManager()->getNewQuery($themeModel);
			$query->andPredicates($query->eq('name', $themeName));
			return $query->getFirstDocument();
		}
		return null;
	}
}