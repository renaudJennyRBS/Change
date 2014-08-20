<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Generic\Events\Http\Admin;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Generic\Events\Http\Admin\Listeners
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
		$events->attach(\Change\Http\Event::EVENT_ACTION, array($this, 'registerActions'));
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

	/**
	 * @param \Change\Http\Event $event
	 */
	public function registerActions(\Change\Http\Event $event)
	{
		$relativePath = $event->getParam('resourcePath');
		if ($relativePath === 'Rbs/Geo/addressFiltersDefinition.twig')
		{
			$event->setAction(function ($event)
			{
				(new \Rbs\Geo\Http\Admin\Actions\AddressFiltersDefinition())->execute($event);
			});
		}
	}
}