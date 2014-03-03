<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Dev\Events;

/**
 * @name \Rbs\Dev\Events\DevLogging
 */
class DevLogging
{
	/**
	 * @var \Change\Logging\Logging
	 */
	protected $logging = null;

	/**
	 * @return \Change\Logging\Logging
	 */
	protected function getLogging()
	{
		if ($this->logging === null)
		{
			$application = new \Change\Application();
			$this->logging = new \Change\Logging\Logging();
			$this->logging->setWorkspace($application->getWorkspace())->setConfiguration($application->getConfiguration());
		}
		return $this->logging;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return boolean
	 */
	public function logBeginEvent(\Zend\EventManager\Event $event)
	{
		$this->getLogging()->debug('START of '. get_class($event). '::'. $event->getName() . ' thrown by ' . get_class($event->getTarget()));
		return true;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return boolean
	 */
	public function logEndEvent(\Zend\EventManager\Event $event)
	{
		$this->getLogging()->debug('END of '. get_class($event). '::'. $event->getName() . ' thrown by ' . get_class($event->getTarget()));
		return true;
	}
}