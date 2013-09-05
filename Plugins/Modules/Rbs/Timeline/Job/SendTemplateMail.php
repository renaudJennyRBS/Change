<?php
namespace Rbs\Timeline\Job;

use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Timeline\Job\SendTemplateMail
 */
class SendTemplateMail
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$documentServices = $event->getDocumentServices();

		$themeManager = (new PresentationServices($documentServices->getApplicationServices()))->getThemeManager();
		$themeManager->setDocumentServices($documentServices);

		$theme = $themeManager->getByName('Rbs_Demo');

		$template = $themeManager->getMailTemplate($job->getArgument('templateCode'), $theme);
		if ($template)
		{
			$mm = $documentServices->getApplicationServices()->getMailManager();
			$message = $mm->composeTemplateMessage($template, $job->getArgument('params'), null,
				['noreply@change4.fr'], $job->getArgument('to'));
			$mm->send($message);
		}
		$event->success();
	}
}