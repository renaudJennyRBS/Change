<?php
/**
 * Copyright (C) 2014 Eric Hauswald
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Documents\Events;

/**
 * @name \Change\Documents\Events\InlineEvent
 */
class InlineEvent extends \Change\Events\Event
{
	/**
	 * @throws \RuntimeException
	 * @return \Change\Documents\AbstractInline
	 */
	public function getDocument()
	{
		if ($this->getTarget() instanceof \Change\Documents\AbstractInline)
		{
			return $this->getTarget();
		}
		throw new \RuntimeException('Invalid inline document instance', 50000);
	}
}