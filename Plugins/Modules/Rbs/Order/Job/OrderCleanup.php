<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Order\Job;

/**
 * @name \Rbs\Order\Job\OrderCleanup
 */
class OrderCleanup
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$modelName = $job->getArgument('model');
		$model = $applicationServices->getModelManager()->getModelByName($modelName);
		if ($model && $model->getName() == 'Rbs_Order_Order')
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			if ($commerceServices instanceof \Rbs\Commerce\CommerceServices)
			{
				$stockManager = $commerceServices->getStockManager();

				$job = $event->getJob();
				$stockManager->unsetReservations('Order:' . $job->getArgument('id'));
			}
			else
			{
				$event->getApplicationServices()->getLogging()->error('Commerce services not set in: ' . __METHOD__);
			}
		}
	}
} 