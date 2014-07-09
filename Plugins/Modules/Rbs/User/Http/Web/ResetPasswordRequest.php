<?php
/**
 * Copyright (C) 2014 Ready Business System
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\User\Http\Web\ResetPasswordRequest
 */
class ResetPasswordRequest extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return \Change\Http\Web\Result\AjaxResult|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			// Instantiate constraint manager to register locales in validation.
			$event->getApplicationServices()->getConstraintsManager();
			$email = $event->getRequest()->getPost('email');

			if ($email !== null)
			{
				// Try to get user by email (login)
				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
				$dqb->andPredicates($dqb->eq('email', $email));
				$user = $dqb->getFirstDocument();

				if ($user)
				{
					$date = new \DateTime();
					$token = md5($email . $date->getTimestamp());

					// Add registration
					$tm = $event->getApplicationServices()->getTransactionManager();
					try
					{
						$tm->begin();

						$dbProvider = $event->getApplicationServices()->getDbProvider();
						$qb = $dbProvider->getNewStatementBuilder();
						$fb = $qb->getFragmentBuilder();

						$qb->insert($fb->table('rbs_user_reset_password'));
						$qb->addColumns($fb->column('user_id'), $fb->column('token'), $fb->column('request_date'));
						$qb->addValues($fb->parameter('user_id'), $fb->parameter('token'), $fb->dateTimeParameter('requestDate'));
						$iq = $qb->insertQuery();

						$iq->bindParameter('user_id', $user->getId());
						$iq->bindParameter('token', $token);
						$iq->bindParameter('requestDate', $date);
						$iq->execute();

						$tm->commit();
					}
					catch (\Exception $e)
					{
						throw $tm->rollBack($e);
					}

					// Send email
					// Send a mail to confirm email.
					$documentManager = $event->getApplicationServices()->getDocumentManager();

					$LCID = $event->getRequest()->getLCID();

					/** @var $website \Rbs\Website\Documents\Website */
					$website = $event->getWebsite();

					$documentManager->pushLCID($LCID);
					$urlManager = $website->getUrlManager($LCID);

					$confirmationURL = $urlManager->getAjaxURL('Rbs_User', 'ResetPasswordConfirmation', ['token' => $token]);

					/* @var \Rbs\Generic\GenericServices $genericServices */
					$genericServices = $event->getServices('genericServices');
					$mailManager = $genericServices->getMailManager();
					try
					{
						$mailManager->send('user_reset_password_request', $website, $LCID, $email,
							['website' => $website->getTitle(), 'link' => $confirmationURL]);
					}
					catch (\RuntimeException $e)
					{
						$event->getApplicationServices()->getLogging()->info($e);
					}
					$documentManager->popLCID();
				}
			}

			$event->setResult($this->getNewAjaxResult());
		}
	}
}