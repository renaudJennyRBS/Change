<?php
namespace Rbs\User\Job;

/**
 * @name \Rbs\User\Job\SendMail
 */
class SendMail
{
	/**
	 * @param \Change\Job\Event $event
	 */
	public function execute(\Change\Job\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$args = $event->getJob()->getArguments();

		$themeManager = $applicationServices->getThemeManager();

		$themeName = $args['themeName'];
		$templateCode = $args['templateCode'];
		$params = $args['params'];
		$email = $args['email'];

		if ($themeName && $templateCode && $params && $email)
		{
			$theme = $event->getApplicationServices()->getThemeManager()->getByName($themeName);
			$template = $themeManager->getMailTemplate($templateCode, $theme);

			if ($template)
			{
				$mailManager = $applicationServices->getMailManager();
				//FIXME 'from' is hardcoded (noreply@change4.fr)
				$message = $mailManager->composeTemplateMessage($template, $params, null,
					['noreply@change4.fr'], [$email]);
				$mailManager->send($message);
				$event->success();
			}
			else
			{
				$event->failed('Invalid mail template given');
			}
		}
		else
		{
			$event->failed('Invalid arguments');
		}
	}
}