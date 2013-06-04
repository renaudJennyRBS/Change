<?php
namespace Rbs\Admin;

/**
 * @name \Rbs\Admin\Event
 */
class Event extends \Zend\EventManager\Event
{
	const EVENT_RESOURCES = 'resources';
	/**
	 * @api
	 * @throws \RuntimeException
	 * @return \Rbs\Admin\Manager
	 */
	public function getManager()
	{
		if ($this->getTarget() instanceof \Rbs\Admin\Manager)
		{
			return $this->getTarget();
		}
		throw new \RuntimeException('Invalid event target type', 99999);
	}
}