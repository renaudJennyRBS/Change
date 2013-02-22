<?php
namespace Change\Http\Web;

/**
 * @name \Change\Http\Web\Resolver
 */
class Resolver extends \Change\Http\ActionResolver
{
	/**
	 * @param \Change\Http\Event $event
	 * @return void
	 */
	public function resolve(\Change\Http\Event $event)
	{
		$request = $event->getRequest();
		$event->getApplicationServices()->getLogging()->warn(__METHOD__ . ': '. $request->getPath());
	}
}