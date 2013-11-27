<?php
namespace Rbs\Admin\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Rest\Actions\GetConnectionInfo
 */
class GetConnectionInfo
{

	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$user = $event->getAuthenticationManager()->getCurrentUser();

		if ($user)
		{
			$dqb = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_User_User');
			$dqb->andPredicates($dqb->eq('id', $user->getId()));
			$user = $dqb->getFirstDocument();
			/* @var $user \Rbs\User\Documents\User */
			if ($user)
			{
				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
				$result->setArray([
					'id' => $user->getId(),
					'email' => $user->getEmail(),
					'login' => $user->getLogin()
				]);
				$event->setResult($result);
			}
			else
			{
				$result = new \Change\Http\Rest\Result\ArrayResult();
				$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
				$result->setArray(['error' => 'user in database not found']);
				$event->setResult($result);
			}
		}
		else
		{
			$result = new \Change\Http\Rest\Result\ArrayResult();
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
			$result->setArray(['error' => 'current user not found']);
			$event->setResult($result);
		}
	}
}