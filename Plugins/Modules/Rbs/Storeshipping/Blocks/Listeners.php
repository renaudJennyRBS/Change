<?php
/**
 * Copyright (C) 2014 Proximis
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Storeshipping\Blocks;

use Change\Presentation\Blocks\Standard\RegisterByBlockName;

/**
 * @name \Rbs\Storeshipping\Blocks\Listeners
 */
class Listeners implements \Zend\EventManager\ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @return void
	 */
	public function attach(\Zend\EventManager\EventManagerInterface $events)
	{
		new RegisterByBlockName('Rbs_Storeshipping_ShortStore', true, $events);
	}

	/**
	 * Detach all previously attached listeners
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @return void
	 */
	public function detach(\Zend\EventManager\EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}