<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\UpdateConnectionInfo
 */
class UpdateConnectionInfo
{

	/**
	 * @param \Change\Http\Event $event
	 * @throws \Exception
	 */
	public function execute($event)
	{
		$user = $event->getAuthenticationManager()->getCurrentUser();
		$args = $event->getRequest()->getPost()->toArray();
		$userConnectionInfo = isset($args['user']) ? $args['user'] : null;
		$password = isset($args['password']) ? $args['password'] : null;

		if ($userConnectionInfo && isset($userConnectionInfo['id']))
		{
			if ($user && $user->getId() === $userConnectionInfo['id'])
			{
				$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
				$dqb->andPredicates($dqb->eq('id', $userConnectionInfo['id']));
				$user = $dqb->getFirstDocument();
				/* @var $user \Rbs\User\Documents\User */
				if ($user)
				{
					if ($user->checkPassword($password))
					{
						$email = isset($userConnectionInfo['email']) ? $userConnectionInfo['email'] : null;
						if ($email && $email !== $user->getEmail())
						{
							$user->setEmail($email);
						}

						$login = isset($userConnectionInfo['login']) ? $userConnectionInfo['login'] : null;
						if ($login && $login !== $user->getLogin())
						{
							$user->setLogin($login);
						}

						$password = isset($userConnectionInfo['password']) ? $userConnectionInfo['password'] : null;
						if ($password)
						{
							$user->setPassword($password);
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
						(new GetConnectionInfo())->execute($event);
					}
					else
					{
						$result = new \Change\Http\Rest\Result\ArrayResult();
						$result->setHttpStatusCode(HttpResponse::STATUS_CODE_403);
						$result->setArray(['code' => 999999, 'message' => 'wrong password given']);
						$event->setResult($result);
					}
				}
				else
				{
					$result = new \Change\Http\Rest\Result\ArrayResult();
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
					$result->setArray(['code' => 71001, 'message' => 'Document ' . $userConnectionInfo['id'] . ' not found (user id)']);
					$event->setResult($result);
				}
			}
			else
			{
				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_403);
				$result->setArray(['code' => 999999, 'message' => 'user id given not matches current user id']);
				$event->setResult($result);
			}
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_500);
			$result->setArray(['code' => 71000, 'message' => 'Invalid Parameter: user.id']);
			$event->setResult($result);
		}
	}
}