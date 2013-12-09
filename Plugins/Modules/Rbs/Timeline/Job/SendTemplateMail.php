<?php
namespace Rbs\Timeline\Job;

/**
 * @name \Rbs\Timeline\Job\SendTemplateMail
 */
class SendTemplateMail
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();

		$themeManager = $applicationServices->getThemeManager();

		$theme = $themeManager->getByName('Rbs_Demo');

		$template = $themeManager->getMailTemplate($job->getArgument('templateCode'), $theme);
		if ($template)
		{
			$mm = $applicationServices->getMailManager();
			$message = $mm->composeTemplateMessage($template, $job->getArgument('params'), null,
				['noreply@change4.fr'], $job->getArgument('to'));
			$mm->send($message);
		}
		$event->success();
	}
}