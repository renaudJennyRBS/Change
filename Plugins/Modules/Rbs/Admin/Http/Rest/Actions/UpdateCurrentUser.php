<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\UpdateCurrentUser
 */
class UpdateCurrentUser
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$profileManager = $event->getApplicationServices()->getProfileManager();
		$props = $event->getRequest()->getPost()->toArray();

		$profile = $profileManager->loadProfile($user, 'Change_User');
		if ($profile)
		{
			$save = false;
			foreach ($profile->getPropertyNames() as $name)
			{
				if (isset($props[$name]))
				{
					$profile->setPropertyValue($name, $props[$name]);
					$save = true;
				}
			}
			if ($save)
			{
				$profileManager->saveProfile($user, $profile);
			}
		}

		$profile = $profileManager->loadProfile($user, 'Rbs_Admin');
		if ($profile)
		{
			$save = false;
			foreach ($profile->getPropertyNames() as $name)
			{
				if (isset($props[$name]))
				{
					$profile->setPropertyValue($name, $props[$name]);
					$save = true;
				}
			}
			if ($save)
			{
				$profileManager->saveProfile($user, $profile);
			}
		}

		(new GetCurrentUser())->execute($event);
	}
}