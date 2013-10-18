<?php
namespace Rbs\Event\Documents;

/**
 * @name \Rbs\Event\Documents\BaseEvent
 */
abstract class BaseEvent extends \Compilation\Rbs\Event\Documents\BaseEvent
{
	protected function onCreate()
	{
		$this->fixDates();
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('date') || $this->isPropertyModified('endDate'))
		{
			$this->fixDates();
		}
	}

	abstract protected function fixDates();
}