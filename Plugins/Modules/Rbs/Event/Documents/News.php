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
 * @name \Rbs\Event\Documents\News
 */
class News extends \Compilation\Rbs\Event\Documents\News
{
	/**
	 * If the date is empty, set it to now.
	 * Synchronise date and endDate.
	 */
	protected function fixDates()
	{
		if ($this->getDate() === null)
		{
			$this->setDate(new \DateTime());
		}
		$this->setEndDate($this->getDate());
	}
}