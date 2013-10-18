<?php
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
	 * @return boolean
	 */
	public function onOneDay()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		return $i18n->transDate($this->getDate()) == $i18n->transDate($this->getEndDate());
	}

	/**
	 * @return boolean
	 */
	public function onOneMinute()
	{
		$i18n = $this->getApplicationServices()->getI18nManager();
		return $i18n->transDateTime($this->getDate()) == $i18n->transDateTime($this->getEndDate());
	}
}