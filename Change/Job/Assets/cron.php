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


class cron
{

	/**
	 * @var \Change\Events\EventManager
	 */
	protected $eventManager;

	/**
	 * @param \Change\Application $application
	 */
	function __construct($application)
	{
		$this->eventManager = $application->getNewEventManager('cron');
		$this->eventManager->attach('execute', [$this, 'onExecute'], 5);
	}

	public function execute()
	{
		$this->eventManager->trigger('execute');
	}

	public function onExecute(\Change\Events\Event $event)
	{
		$logging = $event->getApplication()->getLogging();
		$logging->info('Cron check runnable jobs...');
		$applicationServices = $event->getApplicationServices();

		$jobManager = $applicationServices->getJobManager();
		$runnableJobIds = $jobManager->getRunnableJobIds();
		if (count($runnableJobIds))
		{
			foreach($jobManager->getRunnableJobIds() as $jobId)
			{
				$logging->info('Run: ' . $jobId);
				$job = $jobManager->getJob($jobId);
				$jobManager->run($job);
			}
		}
	}
}

echo 'Check jobs at ', (new \DateTime())->format(\DateTime::ISO8601), PHP_EOL;
$cron = new cron($application);
$cron->execute();
