<?php
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