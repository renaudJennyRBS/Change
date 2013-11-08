<?php
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
