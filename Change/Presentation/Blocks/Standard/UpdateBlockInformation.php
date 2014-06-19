<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Blocks\Standard;

use Zend\EventManager\EventManagerInterface;
use Change\Presentation\Blocks\BlockManager;

/**
* @name \Change\Presentation\Blocks\Standard\UpdateBlockInformation
*/
class UpdateBlockInformation
{
	/**
	 * @api
	 * @param string $blockName
	 * @param \Zend\EventManager\EventManagerInterface $events
	 * @param Callable $callable
	 */
	function __construct($blockName, EventManagerInterface $events,  $callable)
	{
		$events->attach(BlockManager::composeEventName(BlockManager::EVENT_INFORMATION, $blockName), $callable, 5);
	}
} 