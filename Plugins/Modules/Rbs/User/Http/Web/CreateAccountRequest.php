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
			$email = $data['email'];
			$password = $data['password'];
			$parametersErrors = $this->getParametersErrors($event);

			if (count($parametersErrors) === 0)
			{
				$tm = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();

					//TODO security issue?
					$parameters = [
						'password' => $password,
						'groupIds' => isset($data['groupIds']) && $data['groupIds'] ? $data['groupIds'] : '[]'
					];
					$dbProvider = $event->getApplicationServices()->getDbProvider();
					$qb = $dbProvider->getNewStatementBuilder();
					$fb = $qb->getFragmentBuilder();

					$qb->insert($fb->table($fb->getSqlMapping()->getUserAccountRequestTable()));
					$qb->addColumns($fb->column('email'), $fb->column('config_parameters'), $fb->column('validity_date'));
					$qb->addValues($fb->parameter('email'), $fb->parameter('configParameters'), $fb->dateTimeParameter('validityDate'));
					$iq = $qb->insertQuery();

					$iq->bindParameter('email', $email);
					$iq->bindParameter('configParameters', json_encode($parameters));
					$validityDate = new \DateTime();
					$validityDate->add(new \DateInterval('PT24H'));
					$iq->bindParameter('validityDate', $validityDate);
					$iq->execute();

					$requestId = intval($dbProvider->getLastInsertId($dbProvider->getSqlMapping()->getUserAccountRequestTable()));

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
					$event->getApplicationServices()->getDocumentManager()->pushLCID($LCID);
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

					$jobManager = $event->getApplicationServices()->getJobManager();
					$arguments = [
						'themeName' => 'Rbs_Demo',
						'templateCode' => 'createAccountRequest',
						'params' => $params,
						'email' => $email
					];
					$jobManager->createNewJob('Rbs_User_SendMail', $arguments);
					$event->getApplicationServices()->getDocumentManager()->popLCID();

					//Redirect the user to a confirmation page
					$result = new \Change\Http\Web\Result\AjaxResult($data);
					$event->setResult($result);
				}
				catch (\Exception $e)
				{
					$event->getApplicationServices()->getDocumentManager()->popLCID();
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

		if (!$email)
		{
			$errors[] = $i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']);
		}
		else
		{
			$validator = new \Zend\Validator\EmailAddress();
			if (!$validator->isValid($email))
			{
				$errors[] = implode(', ', $validator->getMessages());
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
					if ($accountRequest && $accountRequest['validity_date']->getTimestamp() > (new \DateTime())->getTimestamp())
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
		$qb->select($fb->column('request_id'), $fb->column('validity_date'));
		$qb->from($fb->table($fb->getSqlMapping()->getUserAccountRequestTable()));
		$qb->where($fb->logicAnd(
			$fb->eq($fb->column('email'), $fb->parameter('email'))
		));
		$qb->orderDesc($fb->column('request_id')); //define an order to get the last request
		$sq = $qb->query();

		$sq->bindParameter('email', $email);
		$sq->bindParameter('now', (new \DateTime()));
		return $sq->getFirstResult($sq->getRowsConverter()->addIntCol('request_id')->addDtCol('validity_date'));
	}
}