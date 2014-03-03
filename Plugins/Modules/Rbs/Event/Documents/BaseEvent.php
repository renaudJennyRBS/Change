<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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