<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Events;

/**
* @name \Change\Events\EventsCapableTrait
*/
trait EventsCapableTrait
{
	/**
	 * @var \Change\Events\EventManager
	 */
	protected $eventManager;

	/**
	 * @var \Change\Application
	 */
	protected $application;

	/**
	 * @param \Change\Application $application
	 * @return $this
	 */
	public function setApplication(\Change\Application $application)
	{
		$this->application = $application;
		return $this;
	}

	/**
	 * @return \Change\Application
	 */
	protected function getApplication()
	{
		return $this->application;
	}

	/**
	 * @return null|string|string[]
	 */
	protected function getEventManagerIdentifier()
	{
		return null;
	}

	/**
	 * @return string[]|null
	 */
	protected function getListenerAggregateClassNames()
	{
		return null;
	}

	/**
	 * @param \Change\Events\EventManager $eventManager
	 */
	public function setEventManager(\Change\Events\EventManager $eventManager)
	{
		$this->clearEventManager();
		$this->eventManager = $eventManager;
		$this->attachEvents($this->eventManager);
	}

	/**
	 * @throws \RuntimeException
	 * @return \Change\Events\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->setEventManager($this->getApplication()->getNewEventManager($this->getEventManagerIdentifier(), $this->getListenerAggregateClassNames()));
		}
		return $this->eventManager;
	}

	/**
	 * @api
	 */
	public function clearEventManager()
	{
		if (isset($this->eventManager))
		{
			foreach ($this->eventManager->getEvents() as $event)
			{
				$this->eventManager->clearListeners($event);
			}
			$this->eventManager = null;
		}
	}

	/**
	 * @api
	 * @param \Change\Events\EventManager $eventManager
	 */
	protected function attachEvents(\Change\Events\EventManager $eventManager)
	{
	}
}