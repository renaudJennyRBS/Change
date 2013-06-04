<?php
namespace Change\Users\Blocks;

use Change\Presentation\Blocks\Standard\RegisterByBlockName;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

/**
 * @name \Change\Users\Blocks\SharedListenerAggregate
 */
class SharedListenerAggregate implements SharedListenerAggregateInterface
{
	/**
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		new  RegisterByBlockName('Change_Users_Login', true, $events);
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
	}
}
