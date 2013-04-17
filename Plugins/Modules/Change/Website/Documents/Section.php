<?php
namespace Change\Website\Documents;

use Change\Documents\Events\Event;

/**
 * @name \Change\Website\Documents\Section
 */
class Section extends \Compilation\Change\Website\Documents\Section
{
	/**
	 * @return string
	 */
	public function getPathSuffix()
	{
		return '/';
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		$eventManager->attach(Event::EVENT_DISPLAY_PAGE, array($this, 'onDocumentDisplayPage'), 5);
	}

	/**
	 * @param Event $event
	 * @return \Change\Presentation\Interfaces\Page|null
	 */
	public function onDocumentDisplayPage($event)
	{
		if ($event instanceof Event)
		{
			$document = $event->getDocument();
			if ($document instanceof Section)
			{
				$page = $document->getIndexPage();
				if ($page instanceof \Change\Presentation\Interfaces\Page)
				{
					return $page;
				}
			}
		}
		return null;
	}
}