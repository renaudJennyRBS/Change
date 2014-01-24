<?php
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
			$parametersErrors = $this->getParametersErrors($event);

			if (count($parametersErrors) === 0)
			{
				$email = $data['email'];
				$password = $data['password'];

				$documentManager = $event->getApplicationServices()->getDocumentManager();
				//create an unsaved user to get the password hash and the hash method
				/* @var $user \Rbs\User\Documents\User */
				$user = $documentManager->getNewDocumentInstanceByModelName('Rbs_User_User');
				$user->setPassword($password);

				$parameters = [
					'passwordHash' => $user->getPasswordHash(),
					'hashMethod' => $user->getHashMethod()
				];

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
				catch(\Exception $e)
				{
					throw $tm->rollBack($e);
				}

				$LCID = $event->getRequest()->getLCID();
				//Send a mail to confirm email
				try
				{
					$documentManager->pushLCID($LCID);
					$urlManager = $event->getUrlManager();
					$urlManager->setAbsoluteUrl(true);

					$query = [
						'requestId' => $requestId,
						'email' => $email
					];
					$params = [
						'website' => $event->getWebsite()->getTitle(),
						'link' => $urlManager->getAjaxURL('Rbs_User', 'CreateAccountConfirmation', $query)
					];

					/* @var \Rbs\Generic\GenericServices $genericServices */
					$genericServices = $event->getServices('genericServices');
					$mailManager = $genericServices->getMailManager();
					$mailManager->send('user_account_request', $event->getWebsite(), $LCID, $email, $params);

					$documentManager->popLCID();

					$result = new \Change\Http\Web\Result\AjaxResult($data);
					$event->setResult($result);
				}
				catch (\Exception $e)
				{
					$event->getApplicationServices()->getLogging()->exception($e);
					$documentManager->popLCID();
					throw $e;
				}
			}
			else
			{
				$result = new \Change\Http\Web\Result\AjaxResult(['errors' => $parametersErrors]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
				$event->setResult($result);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @return array
	 */
	protected function getParametersErrors(\Change\Http\Web\Event $event)
	{
		$errors = [];
		// Instantiate constraint manager to register locales in validation.
		$event->getApplicationServices()->getConstraintsManager();
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$data = $event->getRequest()->getPost()->toArray();
		$email = $data['email'];
		$password = $data['password'];
		$confirmPassword = $data['confirmpassword'];

		if (!$email)
		{
			$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']);
		}
		else
		{
			$validator = new \Zend\Validator\EmailAddress();
			if (!$validator->isValid($email))
			{
				//We cannot use validator messages, they are too complicated for front office
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
					//check if request date is not too close (delta of 24h after the request)
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
		$qb->orderDesc($fb->column('request_id')); //define an order to get the last request
		$sq = $qb->query();

		$sq->bindParameter('email', $email);
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('request_id')->addDtCol('request_date'));
	}
}