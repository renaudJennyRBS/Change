<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\PathRuleManager;

use Change\Http\Web\PathRuleManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\PathRuleManager\Listeners
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
			(new \Rbs\Seo\Std\PathTemplateComposer())->onPopulatePathRule($event);
		};
		$events->attach(PathRuleManager::EVENT_POPULATE_PATH_RULE, $callback, 15);

		$callback = function (\Change\Events\Event $event)
		{
			(new \Rbs\Website\Events\PageResolver())->onPopulatePathRule($event);
		};
		$events->attach(PathRuleManager::EVENT_POPULATE_PATH_RULE, $callback, 10);
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