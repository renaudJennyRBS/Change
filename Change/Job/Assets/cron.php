<?php
define('PROJECT_HOME',  dirname(dirname(dirname(__DIR__))));
require_once(PROJECT_HOME . '/Change/Application.php');

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