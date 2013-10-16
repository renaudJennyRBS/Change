<?php
require_once(dirname(dirname(dirname(__DIR__))) . '/Change/Application.php');

$application = new \Change\Application();
$application->start();
$applicationServices = new \Change\Application\ApplicationServices($application);
$documentServices = new \Change\Documents\DocumentServices($applicationServices);
$commonServices = new \Change\Services\CommonServices($applicationServices, $documentServices);

$jobManager = $commonServices->getJobManager();
$jobManager->setCommonServices($commonServices);

$applicationServices->getLogging()->info('Cron check runnable jobs...');
foreach($jobManager->getRunnableJobIds() as $jobId)
{
	$jobManager->getApplicationServices()->getLogging()->info('Run: ' . $jobId);
	$job = $jobManager->getJob($jobId);
	$jobManager->run($job);
}