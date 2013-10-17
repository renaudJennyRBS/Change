<?php
namespace Rbs\Dev\Events;

use Zend\Form\Annotation\Object;

/**
 * @name \Rbs\Dev\Events\DevLogging
 */
class DevLogging
{

	protected $logging = null;

	public function __construct()
	{
	}

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

	public function logBeginEvent(\Zend\EventManager\Event $event)
	{
		$this->getLogging()->debug('START of event ' . $event->getName() . ' thrown by ' . get_class($event->getTarget()));
	}

	public function logEndEvent(\Zend\EventManager\Event $event)
	{
		$this->getLogging()->debug('END of event ' . $event->getName() . ' thrown by ' . get_class($event->getTarget()));
	}
}