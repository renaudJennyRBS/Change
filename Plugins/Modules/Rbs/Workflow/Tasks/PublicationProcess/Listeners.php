<?php
namespace Rbs\Workflow\Tasks\PublicationProcess;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Workflow\Tasks\PublicationProcess\Listeners
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
			$task = new RequestValidation();
			$task->execute($event);
		};
		$events->attach('requestValidation', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new ContentValidation();
			$task->execute($event);
		};
		$events->attach('contentValidation', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new CheckPublication();
			$task->execute($event);
		};
		$events->attach('checkPublication', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new PublicationValidation();
			$task->execute($event);
		};
		$events->attach('publicationValidation', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new Freeze();
			$task->execute($event);
		};
		$events->attach('freeze', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new Unfreeze();
			$task->execute($event);
		};
		$events->attach('unfreeze', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new File();
			$task->execute($event);
		};
		$events->attach('file', $callback, 5);
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