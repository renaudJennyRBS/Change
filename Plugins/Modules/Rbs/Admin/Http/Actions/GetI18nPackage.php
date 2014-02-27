<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;

/**
 * @name \Rbs\Admin\Http\Actions\GetI18nPackage
 */
class GetI18nPackage
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$LCID = $event->getParam('LCID', $event->getRequest()->getQuery('LCID'));

		if ($i18nManager->isSupportedLCID($LCID))
		{
			$result = null;
			$packageName = $event->getParam('package');
			$packages = array();
			if ($packageName)
			{
				$keys = $i18nManager->getTranslationsForPackage($packageName, $LCID);
				$package = array();
				if (is_array($keys))
				{
					foreach ($keys as $key => $value)
					{
						$package[$key] = $value;
					}
				}
				$packages[$packageName] = $package;

				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setArray($packages);
			}
			else
			{
				$modules = $event->getApplicationServices()->getPluginManager()->getModules();
				$result = [];
				foreach ($modules as $module)
				{
					if ($module->isAvailable())
					{
						foreach (['admin', 'adminjs', 'documents'] as $subPackage)
						{
							$packageName = implode('.', ['m', strtolower($module->getVendor()), strtolower($module->getShortName()), $subPackage]);
							$keys = $i18nManager->getTranslationsForPackage($packageName, $LCID);
							if (is_array($keys))
							{
								$package = array();
								foreach ($keys as $key => $value)
								{
									$package[$key] = $value;
								}
								$packages[$packageName] = $package;
							}
						}
					}
				}

				$result = new \Rbs\Admin\Http\Result\Renderer();
				$result->setHeaderContentType('application/javascript');
				$result->setRenderer(function() use ($packages)
				{
					if (count($packages))
					{
						return '__change.i18n = ' . json_encode($packages) . ';' . PHP_EOL;
					}
					else
					{
						return '__change.i18n = {};' . PHP_EOL;
					}
				});
			}
			$event->setResult($result);
		}
	}
}