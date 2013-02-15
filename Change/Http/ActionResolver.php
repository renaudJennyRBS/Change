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
	public function resolve(\Change\Http\Event $event)
	{
		$event->setAction(null);
	}
}