<?php
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
	 * @var \Change\Events\EventManagerFactory
	 */
	protected $eventManagerFactory;

	/**
	 * @param \Change\Events\EventManagerFactory $eventManagerFactory
	 * @return $this
	 */
	public function setEventManagerFactory(\Change\Events\EventManagerFactory $eventManagerFactory)
	{
		$this->eventManagerFactory = $eventManagerFactory;
		return $this;
	}

	/**
	 * @return \Change\Events\EventManagerFactory
	 */
	protected function getEventManagerFactory()
	{
		return $this->eventManagerFactory;
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
		$classNames = $this->getListenerAggregateClassNames();
		if (is_array($classNames) && count($classNames))
		{
			$this->eventManagerFactory->registerListenerAggregateClassNames($eventManager, $classNames);
		}
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
			if ($this->eventManagerFactory)
			{
				$this->setEventManager($this->eventManagerFactory->getNewEventManager($this->getEventManagerIdentifier()));
			}
			else
			{
				throw new \RuntimeException('EventManagerFactory not set', 999999);
			}
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