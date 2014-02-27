<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Commands\Events;

/**
* @name \Change\Commands\Events\Event
*/
class Event extends \Change\Events\Event
{

	/**
	 * @var CommandResponseInterface
	 */
	protected $commandResponse;

	/**
	 * @return \Change\Application
	 */
	public function getApplication()
	{
		return $this->getTarget();
	}

	/**
	 * @param \Change\Commands\Events\CommandResponseInterface $commandResponse
	 */
	public function setCommandResponse($commandResponse)
	{
		$this->commandResponse = $commandResponse;
	}

	/**
	 * @return \Change\Commands\Events\CommandResponseInterface
	 */
	public function getCommandResponse()
	{
		return $this->commandResponse;
	}

}