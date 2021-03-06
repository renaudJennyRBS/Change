<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Workflow\Tasks\CorrectionPublicationProcess;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Workflow\Tasks\CorrectionPublicationProcess\Listeners
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
			$task = new PublicationValidation();
			$task->execute($event);
		};
		$events->attach('publicationValidation', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new Cancel();
			$task->execute($event);
		};
		$events->attach('cancel', $callback, 5);

		$callback = function (Event $event)
		{
			$task = new ContentMerging();
			$task->execute($event);
		};
		$events->attach('contentMerging', $callback, 5);
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