<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Transaction;

use Zend\EventManager\EventManagerInterface;

/**
 * @name \Change\Transaction\DefaultListeners
 */
class DefaultListeners implements \Zend\EventManager\ListenerAggregateInterface
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
		$callBack = function(\Change\Events\Event $event) {
			$event->getApplicationServices()->getDbProvider()->beginTransaction($event);
			$event->getApplicationServices()->getDocumentManager()->beginTransaction($event);
		};
		$events->attach(TransactionManager::EVENT_BEGIN, $callBack, 5);

		$callBack = function(\Change\Events\Event $event) {
			$event->getApplicationServices()->getDocumentManager()->commit($event);
			$event->getApplicationServices()->getDbProvider()->commit($event);
		};
		$events->attach(TransactionManager::EVENT_COMMIT, $callBack, 5);

		$callBack = function(\Change\Events\Event $event) {
			$event->getApplicationServices()->getDocumentManager()->rollBack($event);
			$event->getApplicationServices()->getDbProvider()->rollBack($event);
		};
		$events->attach(TransactionManager::EVENT_ROLLBACK, $callBack, 5);
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