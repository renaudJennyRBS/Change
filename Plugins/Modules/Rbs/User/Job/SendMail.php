<?php
namespace Rbs\User\Job;

/**
 * @name \Rbs\User\Job\SendMail
 */
class SendMail
{
	/**
	 * FIXME use front mail management (mail template, system mail, etc...) instead of backoffice mail management
	 * @param \Change\Job\Event $event
	 */
	public function execute(\Change\Job\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$args = $event->getJob()->getArguments();

		$params = $args['params'];
		$email = $args['email'];
		$lcid = $args['LCID'];

		if ($params && $email && $lcid)
		{
			$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'create-account-request-mail-' . $lcid . '.twig';
			if (is_file($filePath))
			{
				$mailManager = $applicationServices->getMailManager();
				$templateManager = $applicationServices->getTemplateManager();
				$i18nManager = $applicationServices->getI18nManager();

				$html = $templateManager->renderTemplateFile($filePath, $params);
				$subject = $i18nManager->transForLCID($lcid, 'm.rbs.user.admin.create_account_request_mail_subject');
				//FIXME 'from' is hardcoded (noreply@change4.fr)
				$message = $mailManager->prepareMessage(['noreply@change4.fr'], [$email], $subject, $html);
				$mailManager->prepareHeader($message, ['Content-type' => 'text/html; charset=utf8']);
				$mailManager->send($message);
			}

			$event->success();
		}
		else
		{
			$event->failed('Invalid arguments');
		}
	}
}