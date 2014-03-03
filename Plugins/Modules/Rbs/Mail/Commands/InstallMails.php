<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Mail\Commands;

use Change\Commands\Events\Event;

/**
 * @name \Rbs\Mail\Commands\InstallMails
 */
class InstallMails
{
	/**
	 * @param Event $event
	 */
	public function execute(Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$response = $event->getCommandResponse();

		$templateCode = $event->getParam('template');
		if ($templateCode)
		{
			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Theme_Template');
			$dqb->andPredicates($dqb->eq('code', $templateCode), $dqb->eq('mailSuitable', true));
			$template = $dqb->getFirstDocument();
			if ($template instanceof \Rbs\Theme\Documents\Template)
			{
				$filters = [];
				$package = $event->getParam('package');
				if ($package)
				{
					$pluginManager = $applicationServices->getPluginManager();
					$plugins = $pluginManager->getInstalledPlugins();
					foreach ($plugins as $plugin)
					{
						if ($package === $plugin->getName() || $package === $plugin->getPackage())
						{
							$filters[] = $plugin->getName();
						}
					}
				}

				if (!$package || ($package && count($filters) > 0))
				{
					/* @var $genericServices \Rbs\Generic\GenericServices */
					$genericServices = $event->getServices('genericServices');
					$mailManager = $genericServices->getMailManager();
					$mailManager->installMails($template, $filters);

					$response->addInfoMessage('Mails installed');
				}
				else
				{
					$response->addErrorMessage('package or module: ' . $package . ' not found');
				}
			}
			else
			{
				$response->addErrorMessage('template suitable for mail with code: ' . $templateCode . ' not found');
			}
		}
		else
		{
			$response->addErrorMessage('no template code given');
		}
	}
}