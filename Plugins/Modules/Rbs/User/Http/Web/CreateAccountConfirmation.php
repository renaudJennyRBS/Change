<?php
namespace Rbs\User\Http\Web;

use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\User\Http\Web\CreateAccountConfirmation
*/
class CreateAccountConfirmation extends \Change\Http\Web\Actions\AbstractAjaxAction
{
	/**
	 * @param \Change\Http\Web\Event $event
	 * @return mixed|void
	 * @throws \Exception
	 */
	public function execute(\Change\Http\Web\Event $event)
	{
		if ($event->getRequest()->getMethod() === 'GET')
		{
			$data = $event->getRequest()->getQuery()->toArray();
			$urlManager = $event->getUrlManager();
			$urlManager->setAbsoluteUrl(true);
			$redirectURL = $urlManager->getByFunction('Rbs_User_AccountSettings', null, ['context' => 'accountSuccess']);
			$event->setParam('redirectLocation', $redirectURL);
			$event->setParam('errorLocation', $redirectURL);

			$email = $data['email'];
			//get request parameters or errors
			$requestParameters = $this->getRequestParameters($event);
			$params = isset($requestParameters['params']) ? $requestParameters['params'] : null;
			if ($params && count($requestParameters['errors']) === 0)
			{
				$user = $event->getApplicationServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_User_User');
				/* @var $user \Rbs\User\Documents\User */
				$user->setEmail($email);
				//TODO security issue?
				$user->setPassword($params['password']);
				$groupIds = isset($params['groupIds']) && $params['groupIds'] ? json_decode($params['groupIds']) : null;
				if (is_array($groupIds))
				{
					$groups = [];
					foreach ($groupIds as $groupId)
					{
						$group = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($groupId);
						if ($group instanceof \Rbs\User\Documents\Group)
						{
							$groups[] = $group;
						}
					}
					$user->setGroups($groups);
				}

				$tm = $event->getApplicationServices()->getTransactionManager();
				try
				{
					$tm->begin();
					$user->save();
					$tm->commit();
				}
				catch (\Exception $e)
				{
					throw $tm->rollBack($e);
				}

				$result = new \Change\Http\Web\Result\AjaxResult($data);
				$event->setResult($result);
			}
			else
			{
				$result = new \Change\Http\Web\Result\AjaxResult(['errors' => $requestParameters['errors']]);
				$result->setHttpStatusCode(\Zend\Http\Response::STATUS_CODE_409);
				$event->setResult($result);
			}
		}
	}

	/**
	 * @param \Change\Http\Web\Event $event
	 * @throws \Exception
	 * @return array
	 */
	protected function getRequestParameters(\Change\Http\Web\Event $event)
	{
		$result = [];
		$result['errors'] = [];
		$i18nManager = $event->getApplicationServices()->getI18nManager();
		$data = $event->getRequest()->getQuery()->toArray();

		$requestId = $data['requestId'];
		$email = $data['email'];
		if (!$requestId)
		{
			$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_request_id', ['ucf']);
		}
		if (!$email)
		{
			$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_email', ['ucf']);
		}
		if ($requestId && $email)
		{
			//check if email match the request, and check if the date is still valid
			$dbProvider = $event->getApplicationServices()->getDbProvider();
			$qb = $dbProvider->getNewQueryBuilder();
			$fb = $qb->getFragmentBuilder();
			$qb->select($fb->column('config_parameters'));
			$qb->from($fb->table($fb->getSqlMapping()->getUserAccountRequestTable()));
			$qb->where($fb->logicAnd(
				$fb->eq($fb->column('request_id'), $fb->integerParameter('requestId')),
				$fb->eq($fb->column('email'), $fb->parameter('email')),
				$fb->gt($fb->column('validity_date'), $fb->dateTimeParameter('now'))
			));
			$sq = $qb->query();

			$sq->bindParameter('requestId', $requestId);
			$sq->bindParameter('email', $email);
			$sq->bindParameter('now', (new \DateTime()));
			$requestParameters = $sq->getFirstResult($sq->getRowsConverter()->addTxtCol('config_parameters'));

			if (!$requestParameters)
			{
				$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_request_expired', ['ucf']);
			}
			else
			{
				$params = json_decode($requestParameters, true);

				//check if user already exist
				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
				$dqb->andPredicates($dqb->eq('email', $email));
				$count = $dqb->getCountDocuments();
				if ($count !== 0)
				{
					$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_user_already_exist', ['ucf'], ['EMAIL' => $email]);
				}
				else
				{
					if (!isset($params['password']) || !$params['password'])
					{
						$result['errors'][] = $i18nManager->trans('m.rbs.user.front.error_empty_password', ['ucf']);
					}
					else
					{
						$result['params'] = $params;
					}
				}
			}
		}
		return $result;
	}
}