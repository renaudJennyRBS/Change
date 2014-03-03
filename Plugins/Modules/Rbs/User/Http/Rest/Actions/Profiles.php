<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Rest\Actions;

use Change\Http\Rest\Result\ArrayResult;
use Rbs\User\Events\AuthenticatedUser;

/**
 * @name \Rbs\User\Http\Rest\Actions\Profiles
 */
class Profiles
{
	public function execute(\Change\Http\Event $event)
	{
		$result = new ArrayResult();
		$pm = $event->getApplicationServices()->getProfileManager();
		$user = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$data = [];
		if ($user instanceof \Rbs\User\Documents\User)
		{
			foreach ($pm->getProfileNames() as $profileName)
			{
				$authenticatedUser = new AuthenticatedUser($user);
				$profile = $pm->loadProfile($authenticatedUser, $profileName);
				if ($profile instanceof \Change\User\ProfileInterface)
				{
					$data[$profileName] = [];
					foreach ($profile->getPropertyNames() as $name)
					{
						$data[$profileName][$name] = $profile->getPropertyValue($name);
					}
				}
			}
		}
		$result->setArray($data);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_200);
		$event->setResult($result);
	}
}