<?php
namespace Change\Http;

/**
 * @name \Change\Http\ActionResolver
 */
class ActionResolver
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function resolve($event)
	{
		$event->setAction(null);
	}
}