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
 * @name \Rbs\Event\Documents\Event
 */
class Event extends \Compilation\Rbs\Event\Documents\Event
{
	/**
	 * If the date is empty, set it to now.
	 * Synchronise date and endDate.
	 */
	protected function fixDates()
	{
		if ($this->getEndDate() === null)
		{
			$this->setEndDate($this->getDate());
		}
	}

	/**
	 * Does the event start and end on the same calendar day ?
	 * @return boolean
	 */
	public function onOneDay()
	{
		$startDate = $this->getDate();
		$startDate->setTime(0, 0, 0);
		$endDate = $this->getEndDate();
		$endDate->setTime(0, 0, 0);
		return $startDate->getTimestamp() == $endDate->getTimestamp();
	}

	/**
	 * Does the event start and end in the same minute ?
	 * @return boolean
	 */
	public function onOneMinute()
	{
		$startDate = $this->getDate();
		$startDate->setTime($startDate->format('H'), $startDate->format('i'), 0);
		$endDate = $this->getEndDate();
		$endDate->setTime($endDate->format('H'), $endDate->format('i'), 0);
		return $startDate->getTimestamp() == $endDate->getTimestamp();
	}
}