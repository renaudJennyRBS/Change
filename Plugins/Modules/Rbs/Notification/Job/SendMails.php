<?php
namespace Rbs\Notification\Job;

/**
 * @name \Rbs\Notification\Job\SendMails
 */
class SendMails
{
	public function execute(\Change\Job\Event $event)
	{
		$applicationServices = $event->getApplicationServices();

		$themeManager = $applicationServices->getThemeManager();

		$theme = $themeManager->getByName('Rbs_Demo');

		$template = $themeManager->getMailTemplate('notifications', $theme);

		if ($template)
		{
			//first check users want be notified by mail
			$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_User_User');
			$dqb->andPredicates($dqb->activated());
			$users = $dqb->getDocuments();

			$profileManager = $applicationServices->getProfileManager();
			$i18nManager = $applicationServices->getI18nManager();

			//now check for each user their new notifications
			foreach ($users as $user)
			{
				$authenticatedUser = new \Rbs\User\Events\AuthenticatedUser($user);
				$adminProfile = $profileManager->loadProfile($authenticatedUser, 'Rbs_Admin');
				$sendNotification = $adminProfile->getPropertyValue('sendNotificationMailImmediately');
				$notificationMailInterval = $adminProfile->getPropertyValue('notificationMailInterval');
				$notificationMailAt = $adminProfile->getPropertyValue('notificationMailAt');
				$lastNotificationMailSentTimestamp = $adminProfile->getPropertyValue('dateOfLastNotificationMailSent');
				if ($notificationMailInterval && $notificationMailAt)
				{
					$interval = new \DateInterval($adminProfile->getPropertyValue('notificationMailInterval'));
					$now = new \DateTime();

					list($hour, $minute) = explode(':', $notificationMailAt);
					$nextSend = (new \DateTime())->setTimestamp($lastNotificationMailSentTimestamp)->add($interval);

					//if interval concerning "day" set time (hour and minute), else that mean concerning "hour" so set only minute
					$interval->d ? $nextSend->setTime(intval($hour), intval($minute)) : $nextSend->setTime($nextSend->format('H'), $minute);

					//check if we can send him an e-mail, comparing the interval between now, user settings, and the last time a mail has been sent
					$sendNotification = $nextSend->getTimestamp() < $now->getTimestamp();
				}
				if ($sendNotification)
				{
					/* @var $user \Rbs\User\Documents\User */
					$dqb = $applicationServices->getDocumentManager()->getNewQuery('Rbs_Notification_Notification');
					$dqb->andPredicates(
						$dqb->eq('userId', $user->getId()),
						$dqb->eq('status', \Rbs\Notification\Documents\Notification::STATUS_NEW)
					);

					if ($dqb->getCountDocuments() > 0)
					{
						$userProfile = $profileManager->loadProfile($authenticatedUser, 'Change_User');
						$timezone = $userProfile->getPropertyValue('TimeZone') != null ? $userProfile->getPropertyValue('TimeZone') : $i18nManager->getTimeZone()->getName();
						$lcid = $userProfile->getPropertyValue('LCID') != null ? $userProfile->getPropertyValue('LCID') : $i18nManager->getDefaultLCID();
						$params = ['timezone' => $timezone, 'LCID' => $lcid];
						$applicationServices->getDocumentManager()->pushLCID($lcid);
						foreach($dqb->getDocuments() as $notification)
						{
							/* @var $notification \Rbs\Notification\Documents\Notification */
							$params['notifications'][] = [
								'message' => $notification->getCurrentLocalization()->getMessage(),
								'creationDate' => $notification->getCurrentLocalization()->getCreationDate()
							];
						}
						$mm = $applicationServices->getMailManager();
						$message = $mm->composeTemplateMessage($template, $params, null,
							['noreply@change4.fr'], [$user->getEmail()]);
						$applicationServices->getDocumentManager()->popLCID();
						$mm->send($message);

						//set date of the last sent mail, useful to compare with the interval next we want send mail
						$adminProfile->setPropertyValue('dateOfLastNotificationMailSent', (new \DateTime())->getTimestamp());
						$profileManager->saveProfile($authenticatedUser, $adminProfile);
					}
				}
			}
		}

		//reschedule the job in 5 minutes
		$reportDate = (new \DateTime())->add(new \DateInterval('PT5M'));
		$event->reported($reportDate);
	}
}