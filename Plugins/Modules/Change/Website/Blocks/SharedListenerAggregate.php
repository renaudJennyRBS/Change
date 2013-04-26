<?php
namespace Change\Website\Blocks;

use Change\Presentation\Blocks\Standard\RegisterByBlockName;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Change\Website\Blocks\SharedListenerAggregate
 */
class SharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		new  RegisterByBlockName('Change_Website_Menu', true, $events);
		new  RegisterByBlockName('Change_Website_Thread', false, $events);
		new  RegisterByBlockName('Change_Website_SiteMap', true, $events);
		new  RegisterByBlockName('Change_Website_Richtext', true, $events);
		new  RegisterByBlockName('Change_Website_Exception', false, $events);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
	}
}
