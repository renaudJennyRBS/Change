<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
require_once(dirname(dirname(dirname(__DIR__))) . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$eventManagerFactory = new \Change\Events\EventManagerFactory($application);

$applicationServices = new \Change\Services\ApplicationServices($application, $eventManagerFactory);
$eventManagerFactory->addSharedService('applicationServices', $applicationServices);
$jobManager = $applicationServices->getJobManager();

$applicationServices->getLogging()->info('Cron check runnable jobs...');
$runnableJobIds = $jobManager->getRunnableJobIds();
if (count($runnableJobIds))
{
	$jobManager->setTransactionManager($applicationServices->getTransactionManager());
	$jobManager->getEventManager()->trigger('registerServices', $jobManager, array('eventManagerFactory' => $eventManagerFactory));
	foreach($jobManager->getRunnableJobIds() as $jobId)
	{
		$applicationServices->getLogging()->info('Run: ' . $jobId);
		$job = $jobManager->getJob($jobId);
		$jobManager->run($job);
	}
}
