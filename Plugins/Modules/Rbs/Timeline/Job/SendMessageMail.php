<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
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