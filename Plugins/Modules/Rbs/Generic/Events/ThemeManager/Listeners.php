<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\ThemeManager;

use Change\Presentation\Themes\ThemeManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\ThemeManager\Listeners
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
		$callback = function (\Change\Events\Event $event)
		{
			$resolver = new \Rbs\Theme\Events\ThemeResolver();
			return $resolver->resolve($event);
		};
		$events->attach(ThemeManager::EVENT_LOADING, $callback, 1);

		$callback = function (\Change\Events\Event $event)
		{
			$resolver = new \Rbs\Theme\Events\MailTemplateResolver();
			return $resolver->resolve($event);
		};
		$events->attach(ThemeManager::EVENT_MAIL_TEMPLATE_LOADING, $callback, 1);

		$callback = function (\Change\Events\Event $event)
		{
			$themeManagerEvents = new \Rbs\Geo\Presentation\ThemeManagerEvents();
			$themeManagerEvents->onAddPageResources($event);
		};
		$events->attach(ThemeManager::EVENT_ADD_PAGE_RESOURCES, $callback);
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