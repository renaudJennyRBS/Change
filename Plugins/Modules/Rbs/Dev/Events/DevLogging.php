<?php
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