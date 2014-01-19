<?php
namespace Rbs\Timeline\Job;

/**
 * @name \Rbs\Timeline\Job\SendMessageMail
 */
class SendMessageMail
{
	public function execute(\Change\Job\Event $event)
	{
		$job = $event->getJob();
		$applicationServices = $event->getApplicationServices();
		$i18nManager = $event->getApplicationServices()->getI18nManager();

		$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'timeline-message-mail-' . $job->getArgument('LCID') . '.twig';
		if (is_file($filePath))
		{
			$mailManager = $applicationServices->getMailManager();
			$templateManager = $applicationServices->getTemplateManager();

			$html = $templateManager->renderTemplateFile($filePath, $job->getArgument('params'));
			$subject = $i18nManager->transForLCID($job->getArgument('LCID'), 'm.rbs.timeline.admin.message_mail_subject');
			// TODO Fixme Don't use hardcoded value
			$message = $mailManager->prepareMessage(['noreply@change4.fr'], $job->getArgument('to'), $subject, $html);
			$mailManager->prepareHeader($message, ['Content-type' => 'text/html; charset=utf8']);
			$mailManager->send($message);
		}

		$event->success();
	}
}