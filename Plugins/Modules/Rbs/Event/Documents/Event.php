<?php
namespace Rbs\Event\Documents;

/**
 * @name \Rbs\Event\Documents\Event
 */
class Event extends \Compilation\Rbs\Event\Documents\Event
{

	/**
	 * @var \Change\I18n\I18nManager
	 */
	private $i18nManager;

	/**
	 * @return \Change\I18n\I18nManager
	 */
	protected function getI18nManager()
	{
		return $this->i18nManager;
	}

	public function onDefaultInjection(\Change\Events\Event $event)
	{
		parent::onDefaultInjection($event);
		$this->i18nManager = $event->getApplicationServices()->getI18nManager();
	}

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
	 * TODO move this on a Manager
	 * @return boolean
	 */
	public function onOneDay()
	{
		$i18n = $this->getI18nManager();
		return $i18n->transDate($this->getDate()) == $i18n->transDate($this->getEndDate());
	}

	/**
	 * TODO move this on a Manager
	 * @return boolean
	 */
	public function onOneMinute()
	{
		$i18n = $this->getI18nManager();
		return $i18n->transDateTime($this->getDate()) == $i18n->transDateTime($this->getEndDate());
	}
}