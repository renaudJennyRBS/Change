<?php
namespace Change\Admin;

/**
 * @name \Change\Admin\Event
 */
class Event extends \Zend\EventManager\Event
{
	const EVENT_RESOURCES = 'resources';
	/**
	 * @api
	 * @throws \RuntimeException
	 * @return \Change\Admin\Manager
	 */
	public function getManager()
	{
		if ($this->getTarget() instanceof \Change\Admin\Manager)
		{
			return $this->getTarget();
		}
		throw new \RuntimeException('Invalid event target type', 99999);
	}
}