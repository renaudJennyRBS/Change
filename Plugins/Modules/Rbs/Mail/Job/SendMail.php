<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Mail\Job;

/**
 * @name \Rbs\Mail\Job\SendMail
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

		$mailId = $args['mailId'];
		$websiteId = $args['websiteId'];
		$emails = $args['emails'];
		$LCID = $args['LCID'];
		$substitutions = $args['substitutions'];

		if ($mailId && $websiteId && $emails && $LCID && is_array($substitutions))
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();
			/* @var $mail \Rbs\Mail\Documents\Mail */
			$mail = $documentManager->getDocumentInstance($mailId);
			/* @var $website \Change\Presentation\Interfaces\Website */
			$website = $documentManager->getDocumentInstance($websiteId);

			if ($mail && $website)
			{
				/* @var $genericServices \Rbs\Generic\GenericServices */
				$genericServices = $event->getServices('genericServices');
				$frontMailManager = $genericServices->getMailManager();
				$documentManager->pushLCID($LCID);
				$html = $frontMailManager->render($mail, $website, $LCID, $substitutions);

				$mailManager = $applicationServices->getMailManager();
				$from = $this->getFrom($mail, $website, $event);
				$subject = $frontMailManager->getSubstitutedString($mail->getCurrentLocalization()->getSubject(), $substitutions);
				$message = $mailManager->prepareMessage($from, $emails['to'], $subject, $html, null, $emails['cc'], $emails['bcc'], $emails['reply-to']);
				$mailManager->prepareHeader($message, ['Content-type' => 'text/html; charset=UTF-8']);
				$mailManager->send($message);
				$documentManager->popLCID();
				$event->success();
			}
			else
			{
				$event->failed('Invalid arguments');
			}
		}
		else
		{
			$event->failed('Invalid arguments');
		}
	}

	/**
	 * @param \Rbs\Mail\Documents\Mail $mail
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @param \Change\Job\Event $event
	 * @return array
	 */
	protected function getFrom($mail, $website, $event)
	{
		if ($mail->getCurrentLocalization()->getSenderMail())
		{
			return [['name' => $mail->getCurrentLocalization()->getSenderName(), 'email' => $mail->getCurrentLocalization()->getSenderMail()]];
		}
		if ($website->getMailSender())
		{
			$mail = $website->getMailSender();
			$senderName = trim(substr($mail, 0, strpos($mail, '<')));
			$senderMail = trim(substr($mail, strpos($mail, '<') + 1));
			$senderMail = trim(substr($senderMail, 0, strrpos($senderMail, '>')));
			return [['name' => $senderName, 'email' => $senderMail]];
		}
		return [$event->getApplication()->getConfiguration('Rbs/Mail/defaultSender')];
	}
}