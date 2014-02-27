<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Admin;

/**
 * @name \Rbs\Admin\Event
 */
class Event extends \Change\Events\Event
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