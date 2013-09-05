<?php
namespace Rbs\Notification\Job;

use Change\Presentation\PresentationServices;

/**
 * @name \Rbs\Notification\Job\SendMails
 */
class SendMails
{
	public function execute(\Change\Job\Event $event)
	{
		$documentServices = $event->getDocumentServices();

		$themeManager = (new PresentationServices($documentServices->getApplicationServices()))->getThemeManager();
		$themeManager->setDocumentServices($documentServices);

		$theme = $themeManager->getByName('Rbs_Demo');

		$template = $themeManager->getMailTemplate('notifications', $theme);

		if ($template)
		{
			//first check users want be notified by mail
			//TODO: try to query users with 'notificationMailInterval' set to something in their Rbs_Admin profile
			//TODO: now just all users
			$dqb = new \Change\Documents\Query\Query($documentServices, 'Rbs_User_User');
			$dqb->andPredicates($dqb->activated());
			$users = $dqb->getDocuments();

			$profileManager = new \Change\User\ProfileManager();
			$profileManager->setDocumentServices($documentServices);
			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			//now check for each user their new notifications
			foreach ($users as $user)
			{
				$authenticatedUser = new \Rbs\User\Events\AuthenticatedUser($user);
				$adminProfile = $profileManager->loadProfile($authenticatedUser, 'Rbs_Admin');
				//if user doesn't set a notification mail interval, that mean he didn't want to be notified by mail.
				if ($adminProfile->getPropertyValue('notificationMailInterval'))
				{
					$interval = new \DateInterval($adminProfile->getPropertyValue('notificationMailInterval'));
					$date = (new \DateTime())->sub($interval)->getTimestamp();
					//check if we can send him a mail, comparing the interval between now, user settings
					//and the last time a mail have been sent
					if ($date > $adminProfile->getPropertyValue('dateOfLastNotificationMailSent'))
					{
						/* @var $user \Rbs\User\Documents\User */
						$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Notification_Notification');
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
							$documentServices->getDocumentManager()->pushLCID($lcid);
							foreach($dqb->getDocuments() as $notification)
							{
								/* @var $notification \Rbs\Notification\Documents\Notification */
								$params['notifications'][] = [
									'message' => $notification->getCurrentLocalization()->getMessage(),
									'creationDate' => $notification->getCurrentLocalization()->getCreationDate()
								];
							}
							$mm = $documentServices->getApplicationServices()->getMailManager();
							$message = $mm->composeTemplateMessage($template, $params, null,
								['noreply@change4.fr'], [$user->getEmail()]);
							$documentServices->getDocumentManager()->popLCID();
							$mm->send($message);

							//set date of the last sent mail, useful to compare with the interval next we want send mail
							$adminProfile->setPropertyValue('dateOfLastNotificationMailSent', (new \DateTime())->getTimestamp());
							$profileManager->saveProfile($authenticatedUser, $adminProfile);
						}
					}
				}
			}
		}

		//reschedule the job in 1 minute
		$reportDate = (new \DateTime())->add(new \DateInterval('PT1M'));
		$event->reported($reportDate);
	}
}