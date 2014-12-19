<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Presentation;

/**
 * @name \Rbs\User\Presentation\PageManager
 */
class PageManager
{
	/**
	 * @param \Change\Presentation\Pages\PageEvent $event
	 */
	public function addUserContext($event)
	{
		if ($event->getParam('TTL') !== 0)
		{
			return;
		}

		$result = $event->getPageResult();
		if ($result->getJsonObject('userContext'))
		{
			return;
		}

		$currentUser = $event->getApplicationServices()->getAuthenticationManager()->getCurrentUser();
		if ($currentUser->authenticated())
		{
			$userContext = [
				'accessorId' => $currentUser->getId(),
				'name' => $currentUser->getName(),
			];
		}
		else
		{
			$userContext = [
				'accessorId' => 0
			];
		}

		$result->setJsonObject('userContext', $userContext);
	}
}