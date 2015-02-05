<?php
namespace Rbs\Social\Events\Http\Rest;

use Change\Http\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Social\Events\Http\Rest\Listeners
 */
class Listeners implements ListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the EventManager
	 * implementation will pass this to the aggregate.
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function attach(EventManagerInterface $events)
	{
		$callback = function (Event $event)
		{
			if (!$event->getAction() && $event->getParam('pathInfo') === 'Rbs/Social/GetSocialData')
			{
				$event->setAction(function (Event $event)
				{
					(new \Rbs\Social\Http\Rest\Actions\GetSocialData())->execute($event);
				});
			}
		};
		$events->attach(Event::EVENT_ACTION, $callback, 5);
	}

	/**
	 * Detach all previously attached listeners
	 * @param EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		// TODO: Implement detach() method.
	}
}