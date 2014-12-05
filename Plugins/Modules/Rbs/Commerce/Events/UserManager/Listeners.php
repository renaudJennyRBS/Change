<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Commerce\Events\UserManager;

use Change\Events\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @name \Rbs\Commerce\Events\UserManager\Listeners
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
			$params = $event->getParam('requestParameters');
			$user = $event->getParam('user');
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices && isset($params['transactionId']) &&
				$user instanceof \Rbs\User\Documents\User)
			{
				$applicationServices = $event->getApplicationServices();
				$transaction = $applicationServices->getDocumentManager()->getDocumentInstance($params['transactionId']);
				if ($transaction instanceof \Rbs\Payment\Documents\Transaction)
				{
					$tm = $applicationServices->getTransactionManager();
					try
					{
						$tm->begin();
						$transaction->setAuthorId($user->getId());
						if (!$transaction->getOwnerId())
						{
							$transaction->setOwnerId($user->getId());
						}
						$transaction->save();
						$commerceServices->getPaymentManager()->handleRegistrationForTransaction($user, $transaction);
						$tm->commit();
					}
					catch (\Exception $e)
					{
						$tm->rollBack($e);
					}
				}
			}
		};
		$events->attach('confirmAccountRequest', $callback);
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