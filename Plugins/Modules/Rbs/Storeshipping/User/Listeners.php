<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\User;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Storeshipping\User\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$profileManagerEvent = new ProfileManagerEvents();

		$events->attach(\Change\User\ProfileManager::EVENT_PROFILES,
			function (Event $event) use ($profileManagerEvent)
			{
				$profileManagerEvent->onProfiles($event);
			});

		$events->attach(\Change\User\ProfileManager::EVENT_LOAD,
			function (Event $event) use ($profileManagerEvent)
			{
				$profileManagerEvent->onLoad($event);
			});

		$events->attach(\Change\User\ProfileManager::EVENT_SAVE,
			function (Event $event) use ($profileManagerEvent)
			{
				$profileManagerEvent->onSave($event);
			});
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}