<?php
namespace Change\Events;

/**
* @name \Change\Events\EventsCapableTrait
*/
trait EventsCapableTrait
{
	/**
	 * @var \Change\Events\SharedEventManager
	 */
	protected $sharedEventManager;

	/**
	 * @var \Zend\EventManager\EventManager
	 */
	protected $eventManager;

	/**
	 * @param \Change\Events\SharedEventManager $sharedEventManager
	 */
	public function setSharedEventManager(\Change\Events\SharedEventManager $sharedEventManager)
	{
		$this->sharedEventManager = $sharedEventManager;
	}

	/**
	 * @return \Change\Events\SharedEventManager
	 */
	public function getSharedEventManager()
	{
		return $this->sharedEventManager;
	}

	/**
	 * @return null|string|string[]
	 */
	abstract protected function getEventManagerIdentifier();

	/**
	 * @return string[]
	 */
	abstract protected function getListenerAggregateClassNames();

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	public function setEventManager(\Zend\EventManager\EventManager $eventManager = null)
	{
		$this->eventManager = $eventManager;
	}

	/**
	 * @return \Zend\EventManager\EventManager
	 */
	public function getEventManager()
	{
		if ($this->eventManager === null)
		{
			$this->eventManager = new \Zend\EventManager\EventManager($this->getEventManagerIdentifier());
			$this->attachEvents($this->eventManager);
		}
		return $this->eventManager;
	}

	/**
	 * @param \Zend\EventManager\EventManager $eventManager
	 */
	protected function attachEvents(\Zend\EventManager\EventManager $eventManager)
	{
		$this->getSharedEventManager()->registerListenerAggregateClassNames($eventManager, $this->getListenerAggregateClassNames());
	}
}