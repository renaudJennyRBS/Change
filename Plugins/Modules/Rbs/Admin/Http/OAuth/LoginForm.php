<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\OAuth;

/**
 * @name \Rbs\Admin\Http\OAuth\LoginForm
 */
class LoginForm
{
	/**
	 * @param \Change\Events\Event $event
	 */
	public function execute($event)
	{
		$data = $event->getParam('data');
		/** @var $httpEvent \Change\Http\Event */
		$httpEvent = $event->getParam('httpEvent');
		if ($data['realm'] === 'Rbs_Admin')
		{
			$applicationServices = $event->getApplicationServices();
			$uri = $httpEvent->getUrlManager()->getSelf();
			$uri->setPath($event->getApplication()->getConfiguration('Change\Install\webBaseURLPath') . '/admin.php/')->setQuery('');
			$data['baseUrl'] = $uri->normalize()->toString();
			$html = $applicationServices->getTemplateManager()->renderTemplateFile(__DIR__ . '/Assets/login.twig', $data);
			$event->setParam('html', $html);
		}
	}
}