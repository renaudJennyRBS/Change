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
			$applicationServices = new \Change\Application\ApplicationServices($application);

			$this->logging = $applicationServices->getLogging();
		}

		return $this->logging;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return bool
	 */
	public function logBeginEvent(\Zend\EventManager\Event $event)
	{
		$this->getLogging()->debug('START of event ' . $event->getName() . ' thrown by ' . get_class($event->getTarget()));
		return true;
	}

	/**
	 * @param \Zend\EventManager\Event $event
	 * @return bool
	 */
	public function logEndEvent(\Zend\EventManager\Event $event)
	{
		$this->getLogging()->debug('END of event ' . $event->getName() . ' thrown by ' . get_class($event->getTarget()));
		return true;
	}
}