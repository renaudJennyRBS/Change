<?php
require_once(dirname(dirname(dirname(__DIR__))) . '/Change/Application.php');

$application = new \Change\Application();
$application->start();

$jobManager = new \Change\Job\JobManager();
$jobManager->setApplicationServices(new \Change\Application\ApplicationServices($application));
$jobManager->getApplicationServices()->getLogging()->info('Cron check runnable jobs...');

foreach($jobManager->getRunnableJobIds() as $jobId)
{
	$jobManager->getApplicationServices()->getLogging()->info('Run: ' . $jobId);
	$job = $jobManager->getJob($jobId);
	$jobManager->run($job);
}