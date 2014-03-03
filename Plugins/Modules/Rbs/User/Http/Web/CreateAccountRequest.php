<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\User\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\CreateAccountRequest
*/
class CreateAccountRequest extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		if ($event->getRequest()->getMethod() === 'POST')
		{
			// Instantiate constraint manager to register locales in validation.
			$event->getApplicationServices()->getConstraintsManager();
			$data = $event->getRequest()->getPost()->toArray();
			$parametersErrors = $this->getParametersErrors($event, $data);
			if (count($parametersErrors) === 0)
			{
				$email = $data['email'];
				$parameters = $this->getAccountRequestParameters($event, $data);
				$LCID = $event->getRequest()->getLCID();
				$website = $event->getWebsite();
				$this->createAccountRequest($event, $email, $parameters, $website, $LCID);

				$event->setResult($this->getSuccessResult($data));
			}
			else
			{
				$event->setResult($this->getErrorResult($parametersErrors, $data));
			}
		}
	}

	/**
	 * @param array $data
	 * @return \Change\Http\Web\Result\AjaxResult
	 */
	protected function getSuccessResult($data)
	{
		unset($data['password']);
		unset($data['confirmPassword']);
		$result = new \Change\Http\Web\Result\AjaxResult($data);
		return $result;
	}

	/**
	 * @param string[] $parametersErrors
	 * @param array $data
	 * @return \Change\Http\Web\Result\AjaxResult
	 */
	protected function getErrorResult($parametersErrors, $data)
	{
		unset($data['password']);
		unset($data['confirmPassword']);
		$result = new \Change\Http\Web\Result\AjaxResult(['errors' => $parametersErrors, 'inputData' => $data]);
		$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
		return $result;
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @param string $email
	 * @param array $parameters
	 * @param string $LCID
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @throws \Exception
	 */
	protected function createAccountRequest(\Change\Http\Web\Event $event, $email, $parameters, $website, $LCID)
	{
		$tm = $event->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();

			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewStatementBuilder();
			$fb = $qb->getFragmentBuilder();

			$qb->insert($fb->table('rbs_user_account_request'));
			$qb->addColumns($fb->column('email'), $fb->column('config_parameters'), $fb->column('request_date'));
			$qb->addValues($fb->parameter('email'), $fb->parameter('configParameters'), $fb->dateTimeParameter('requestDate'));
			$iq = $qb->insertQuery();

			$iq->bindParameter('email', $email);
			$iq->bindParameter('configParameters', json_encode($parameters));
			$iq->bindParameter('requestDate', new \DateTime());
			$iq->execute();

			$requestId = intval($dbProvider->getLastInsertId('rbs_user_account_request'));

			$tm->commit();
		}
		catch (\Exception $e)
		{
			throw $tm->rollBack($e);
		}

		// Send a mail to confirm email.
		$documentManager = $event->getApplicationServices()->getDocumentManager();
		$documentManager->pushLCID($LCID);
		$urlManager = $website->getUrlManager($LCID);
		$urlManager->setAbsoluteUrl(true);

		$query = [
			'requestId' => $requestId,
			'email' => $email
		];
		$params = [
			'website' => $website->getTitle(),
			'link' => $this->getConfirmationUrl($urlManager, $query)
		];

		/* @var \Rbs\Generic\GenericServices $genericServices */
		$genericServices = $event->getServices('genericServices');
		$mailManager = $genericServices->getMailManager();
		try
		{
			$mailManager->send('user_account_request', $website, $LCID, $email, $params);
		}
		catch (\RuntimeException $e)
		{
			$event->getApplicationServices()->getLogging()->info($e);
		}
		$documentManager->popLCID();

	}

	/**
	 * @param \Change\Http\Web\UrlManager $urlManager
	 * @param array $query
	 * @return string
	 */
	protected function getConfirmationUrl($urlManager, $query)
	{
		return $urlManager->getAjaxURL('Rbs_User', 'CreateAccountConfirmation', $query);
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @param array $data
	 * @return array
	 */
	protected function getParametersErrors(\Change\Http\Web\Event $event, $data)
	{
		$errors = [];
		// Instantiate constraint manager to register locales in validation.
		$event->getApplicationServices()->getConstraintsManager();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$email = $data['email'];
		$password = $data['password'];
		$confirmPassword = $data['confirmPassword'];

		if (!$email)
		{
			$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']);
		}
		else
		{
			$validator = new \Zend\Validator\EmailAddress();
			if (!$validator->isValid($email))
			{
				// We cannot use validator messages, they are too complicated for front office.
				$errors[] = $i18nManager->trans('m.rbs.user.front.error_email_invalid', ['ucf'], ['EMAIL' => $email]);
			}
			else
			{
				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
				$dqb->andPredicates($dqb->eq('email', $email));
				$count = $dqb->getCountDocuments();
				if ($count !== 0)
				{
					$errors[] = $i18nManager->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]);
				}
				else
				{
					$accountRequest = $this->getAccountRequestFromEmail($email, $event->getApplicationServices()->getDbProvider());
					$now = new \DateTime();
					// Check if request date is not too close (delta of 24h after the request).
					$now->sub(new \DateInterval('PT24H'));
					if ($accountRequest && $accountRequest['request_date']->getTimestamp() > $now->getTimestamp())
					{
						$errors[] = $i18nManager->trans('m.rbs.user.front.error_request_already_done', ['ucf'], ['EMAIL' => $email]);
					}
				}
			}
		}
		if (!$password)
		{
			$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']);
		}
		else
		{
			if (strlen($password) > 50)
			{
				$errors[] = $i18nManager->trans('m.rbs.user.front.error_password_exceeds_max_characters', ['ucf']);
			}
			if ($password !== $confirmPassword)
			{
				$errors[] = $i18nManager->trans('m.rbs.user.front.error_password_not_match_confirm_password', ['ucf']);
			}
		}

		return $errors;
	}

	/**
	 * @param $email
	 * @param \Change\Db\DbProvider $dbProvider
	 * @return array
	 */
	protected function getAccountRequestFromEmail($email, $dbProvider)
	{
		$qb = $dbProvider->getNewQueryBuilder();
		$fb = $qb->getFragmentBuilder();
		$qb->select($fb->column('request_id'), $fb->column('request_date'));
		$qb->from($fb->table('rbs_user_account_request'));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('email'), $fb->parameter('email'))
		));
		$qb->orderDesc($fb->column('request_id')); // Define an order to get the last request.
		$sq = $qb->query();

		$sq->bindParameter('email', $email);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('request_id')->addDtCol('request_date'));
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @param array $data
	 * @return array
	 */
	protected function getAccountRequestParameters(\Change\Http\Web\Event $event, $data)
	{
		$password = $data['password'];

		$documentManager = $event->getApplicationServices()->getDocumentManager();
		// Create an unsaved user to get the password hash and the hash method.
		/* @var $user \Rbs\User\Documents\User */
		$user = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_User');
		$user->setPassword($password);

		$parameters = [
			'passwordHash' => $user->getPasswordHash(),
			'hashMethod' => $user->getHashMethod()
		];
		return $parameters;
	}
}